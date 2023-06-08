<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Support\Collection;
use League\Csv\Reader;
use League\Csv\Statement;
use TheIconic\NameParser\Language\English;
use TheIconic\NameParser\Name;
use TheIconic\NameParser\Parser;

/**
 * Name parser for importing / saving .csv lists of names
 * // TODO: add exception handling
 * // TODO: add diff file-format import support
 * // TODO: add import batches / transactions for rollback upon failure
 * // TODO: add job processing for large files
 */
class NameParser
{
    protected Parser $parser;

    protected Statement $csv_import;

    protected array $titles;

    protected const SEPARATORS = ['and', '&', ',', '/'];

    public function __construct()
    {
        $this->parser = new Parser();
        $this->csv_import = Statement::create();
        $this->titles = English::SALUTATIONS; // TODO: add multi/external language support
    }

    /**
     * parses single name
     */
    protected function parse_name(string $name): Name
    {
        return $this->parser->parse($name);
    }

    /**
     * we should 'split' our name lines by detecting either multiple titles, or 'and', or '&', or ','
     */
    protected function split_names(string $names): Collection
    {
        $count_titles = collect(array_keys($this->titles))
            ->mapWithKeys(fn ($title) => [$title => preg_match_all('/\b('.$title.')\b/i', $names)])
            ->filter(fn ($count, $title) => $count > 0);

        $count_seperators = collect(self::SEPARATORS)
            ->mapWithKeys(fn ($sep) => [$sep => stripos($names, $sep)])
            ->filter(fn ($count, $title) => $count > -1);
        
        if ($count_titles->count() > 1) {
            // split the names by titles position...
            $split_names = $this->split_by_titles($names, $count_titles);
        } else if ($count_seperators->count() > 0) {
            $split_names = $this->split_by_seperators($names, $count_seperators);
        } else {
            $split_names = collect($this->clean_up_name($names));
        }

        return $split_names;
    }

    /**
     * strips string seperators
     */
    protected function strip_seperators(string $name): string
    {
        return str_ireplace(self::SEPARATORS, '', $name);
    }

    protected function get_title_position(string $str, string $title): int
    {
        preg_match('/\b' . $title . '\b/i', $str, $matches, PREG_OFFSET_CAPTURE);

        return $matches[0][1];
    }

    protected function clean_up_name(string $name): string
    {
        return trim(preg_replace('/[^\w\s\.-]|^[\.]|[\.]$/', '', $name));
    }

    /**
     * split names by title (returns each title found plus rest of string after last found title as separate names)
     */
    protected function split_by_titles(string $names, Collection $titles): Collection
    {
    $titles_pos = $titles->mapWithKeys(fn ($count, $title) => [$title => $this->get_title_position($names, $title)/*strripos($names, $title)*/]);

        $split_name_title = collect($titles)->map(function ($count, $title) use ($names, $titles, $titles_pos) {
            // take given title and map it to the 'rest' of the name.
            // ie. 'Mr/Mrs. Jones' or 'Mr and Mrs. Jones' or 'Mr P Jones & Mrs E Jones'
            // if title has words before next title, use those, else use last name set instead ie. 'Mr & Mrs. Jones'
            
            $last_title_pos = $titles_pos->max();
            $last_title = $titles_pos->search($last_title_pos);
            $last_title_len = strlen($last_title);
            $before_last_title = trim(preg_replace('/\b'.$title.'\b/i', '', $this->strip_seperators(substr($names, 0, $last_title_pos))));
            $before_last_title = trim(str_ireplace($titles->keys()->toArray(), '', $before_last_title));
            $words_before_next_title = preg_match('/\b(\w+)\b/', $before_last_title, $matches);

            if ($words_before_next_title && $title !== $last_title) {
                $rest_of_name = $before_last_title;
            } else {
                $rest_of_name = substr($names, $last_title_pos + $last_title_len);
                $rest_of_name = $this->strip_seperators($rest_of_name);
            }
            $properTitle = $this->titles[$title];

            return "$properTitle {$this->clean_up_name($rest_of_name)}";
        });

        return $split_name_title;
    }

    /**
     * attempts to split name with separators 
     * @param bool $split_once - default true - split by all separators or just first
     * // TODO: test this works with multiple seperators...
     */
    protected function split_by_seperators(string $names, Collection $separators, bool $split_once = true): Collection
    {
        $split_names = collect();
        $separators->each(function ($pos, $sep) use ($names, $split_names) {
            $names = collect(explode($sep, $names));
            $names->each(fn ($name) => $split_names->push($this->clean_up_name($name)));
        })
        ->filter(fn ($name) => strlen(trim($name)) > 0);

        $split_name_sep = $split_once ? $split_names->take(2) : $split_names;

        return $split_name_sep;
    }

    /**
     * converts format of array from NameParser into our DB/Model schema..
     */
    protected function map_name_keys(Name $name): array
    {
        $is_firstName_initial = $this->is_firstName_initial($name);

        return [
            'title' => $name->getSalutation(),
            'first_name' => $is_firstName_initial ? null : $name->getFirstname(),
            'last_name' => ucwords($name->getLastname()),
            'initial' => $is_firstName_initial ? $name->getFirstname() : $name->getInitials(),
        ];
    }

    /**
     * hack to massage initials parsed as firstname by our TheIconic\NameParser Class, painful...
     * // TODO: revisit TheIconic\NameParser package...?
     */
    protected function is_firstName_initial(Name $name)
    {
        return (strlen($name->getFirstname()) > 0 && strlen(str_replace('.','',$name->getFirstname()))<2 && $name->getInitials() === '');
    }

    /**
     * parses line / string of imported name/names
     */
    protected function parse_line(string $line): Collection
    {
        return $this->split_names($line)->map(fn ($name) => $this->map_name_keys($this->parse_name($name)));
    }

    /**
     * used for testing and overloading with just single line of name(s)
     */
    public function parse_names(string $names): Collection
    {
        return $this->parse_line($names);
    }

    /**
     * imports csv, parsing each name line and saving into our Person Model / DB...
     * // TODO: add import batches/limits
     */
    public function import_csv(string $csv_path, int $header_offset = 0): Collection
    {
        $csv = Reader::createFromPath($csv_path, 'r');

        $csv->setHeaderOffset($header_offset);

        $imported_lines = $this->csv_import->process($csv);

        $parsed_names = collect($imported_lines)->mapWithKeys(function ($line_import) {
            $header = array_keys($line_import)[0];

            return [$line_import[$header] => $this->parse_line($line_import[$header])->toArray()];
        });
        
        return $parsed_names->map(fn ($name) => collect($name)->each(fn ($person) => Person::create($person)))->flatten(1);
    }
}
