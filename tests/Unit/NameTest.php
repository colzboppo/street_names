<?php

namespace Tests\Unit;

use App\Services\NameParser;
use Tests\TestCase;

class NameTest extends TestCase
{
    protected NameParser $parser;

    public function setupTests()
    {
        $this->parser = new NameParser();
    }

    /**
     * A test to see we split / parse the correct number of names into the database...
     * @test
     * @group parseAllNames
     */
    public function test_parse_sample_names_split_number(): void
    {
        $this->setupTests();

        $persons = $this->parser->import_csv('tests/names.csv');

        $this->assertCount(18, $persons);
    }

    /**
     * A basic name test for single names, ie. 'Mr. Dave Jones'
     * @test
     * @group parseNames
     */
    public function test_parse_single_name(): void
    {
        $this->setupTests();

        // example 1 'Mr John Smith'
        $this->assertEquals(
            [
                [
                    'title' => 'Mr.',
                    'initial' => null,
                    'first_name' => 'John',
                    'last_name' => 'Smith',
                ],
            ],
            $this->parser->parse_names('Mr John Smith')->toArray()
        );

        // example 2 'Mrs Jane Smith '
        $this->assertEquals(
            [
                [
                    'title' => 'Mrs.',
                    'initial' => null,
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                ],
            ],
            $this->parser->parse_names('Mrs Jane Smith ')->toArray()
        );

        // example 3 'Mister  John     Doe'
        $this->assertEquals(
            [
                [
                    'title' => 'Mr.',
                    'initial' => null,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ],
            $this->parser->parse_names('Mister  John     Doe')->toArray()
        );

        // example 4 'Mr M Mackie'
        $this->assertEquals(
            [
                [
                    'title' => 'Mr.',
                    'initial' => 'M',
                    'first_name' => null,
                    'last_name' => 'Mackie',
                ],
            ],
            $this->parser->parse_names('Mr M Mackie')->toArray()
        );

        // example 5 'Dr. P Gunn'
        $this->assertEquals(
            [
                [
                    'title' => 'Dr.',
                    'initial' => 'P',
                    'first_name' => null,
                    'last_name' => 'Gunn',
                ],
            ],
            $this->parser->parse_names('Dr. P Gunn.')->toArray()
        );

        // example 6 'Ms Claire Robbo'
        $this->assertEquals(
            [
                [
                    'title' => 'Ms.',
                    'initial' => null,
                    'first_name' => 'Claire',
                    'last_name' => 'Robbo',
                ],
            ],
            $this->parser->parse_names('Ms Claire Robbo')->toArray()
        );

        // example 7 'Prof Alex Brogan'
        $this->assertEquals(
            [
                [
                    'title' => 'Prof.',
                    'initial' => null,
                    'first_name' => 'Alex',
                    'last_name' => 'Brogan',
                ],
            ],
            $this->parser->parse_names('Prof Alex Brogan')->toArray()
        );

        // example 8 'Mrs Faye Hughes-Eastwood'
        $this->assertEquals(
            [
                [
                    'title' => 'Mrs.',
                    'initial' => null,
                    'first_name' => 'Faye',
                    'last_name' => 'Hughes-Eastwood',
                ],
            ],
            $this->parser->parse_names('Mrs Faye Hughes-Eastwood')->toArray()
        );

        // example 9 'Ms. Charlotte Van Der Vorst' 
        $this->assertEquals(
            [
                [
                    'title' => 'Ms.',
                    'initial' => null,
                    'first_name' => 'Charlotte',
                    'last_name' => 'Van Der Vorst',
                ],
            ],
            $this->parser->parse_names('Ms. Charlotte Van Der Vorst')->toArray()
        );

        // example 10. 'Ms. Kathleen (Katie) Butler'
        $this->assertEquals(
            [
                [
                    'title' => 'Ms.',
                    'initial' => null,
                    'first_name' => 'Kathleen',
                    'last_name' => 'Butler',
                ],
            ],
            $this->parser->parse_names('Ms. Kathleen (Katie) Butler')->toArray()
        );
    }

    /**
     * A basic name test for multiple/couple names, ie. 'Mr & Mrs. Jones'
     * @test
     * @group parseNames
     */
    public function test_parse_multi_names(): void
    {
        $this->setupTests();

        // example 1. 'Mr & Mrs. Jones'
        $this->assertEquals(
            [
                'mr' => [
                    'title' => 'Mr.',
                    'initial' => null,
                    'first_name' => null,
                    'last_name' => 'Jones',
                ],
                'mrs' => [
                    'title' => 'Mrs.',
                    'initial' => null,
                    'first_name' => null,
                    'last_name' => 'Jones',
                ],
            ], $this->parser->parse_names('Mr & Mrs. Jones')->toArray()
        );

        // example 1.5 'Mr. and Mrs. Jones'
        $this->assertEquals(
            [
                'mr' => [
                    'title' => 'Mr.',
                    'initial' => null,
                    'first_name' => null,
                    'last_name' => 'Jones',
                ],
                'mrs' => [
                    'title' => 'Mrs.',
                    'initial' => null,
                    'first_name' => null,
                    'last_name' => 'Jones',
                ],
            ],
            $this->parser->parse_names('Mr. and Mrs. Jones')->toArray()
        );

        // example 2. 'Mr. D Jones & Mrs. E Jones'
        $this->assertEquals(
            [
                'mr' => [
                    'title' => 'Mr.',
                    'initial' => 'D',
                    'first_name' => null,
                    'last_name' => 'Jones',
                ],
                'mrs' => [
                    'title' => 'Mrs.',
                    'initial' => 'E',
                    'first_name' => null,
                    'last_name' => 'Jones',
                ],
            ],
            $this->parser->parse_names('Mr. D Jones & Mrs. E Jones')->toArray()
        );

        // example 3. 'Mr. David Jones, Ms. Elsie Smith'
        $this->assertEquals(
            [
                'mr' => [
                    'title' => 'Mr.',
                    'initial' => null,
                    'first_name' => 'David',
                    'last_name' => 'Jones',
                ],
                'ms' => [
                    'title' => 'Ms.',
                    'initial' => null,
                    'first_name' => 'Elsie',
                    'last_name' => 'Smith',
                ],
            ],
            $this->parser->parse_names('Mr. David Jones, Ms. Elsie Smith')->toArray()
        );

        // example 4. 'Mr D. Jones & Mrs E. Smith'
        $this->assertEquals(
            [
                'mr' => [
                    'title' => 'Mr.',
                    'initial' => 'D.',
                    'first_name' => null,
                    'last_name' => 'Jones',
                ],
                'mrs' => [
                    'title' => 'Mrs.',
                    'initial' => 'E.',
                    'first_name' => null,
                    'last_name' => 'Smith',
                ],
            ],
            $this->parser->parse_names('Mr D. Jones & Mrs E. Smith')->toArray()
        );

        // TODO: Cannot Parse Sir/Dane as title...
        // example 5. 'Sir David Attenborough (MBE) and Dane Judi Dench'
        // $this->assertEquals(
        //     [
        //         'sir' => [
        //             'title' => 'Sir.',
        //             'initial' => null,
        //             'first_name' => 'David',
        //             'last_name' => 'Attenborough',
        //         ],
        //         'dane' => [
        //             'title' => 'Dane.',
        //             'initial' => null,
        //             'first_name' => 'Judi',
        //             'last_name' => 'Dench',
        //         ],
        //     ],
        //     $this->parser->parse_names('Sir David Attenborough (MBE) and Dane Judi Dench')->toArray()
        // );

        // example 6. 'Prof. D Jones, and Dr. B Smith'
        $this->assertEquals(
            [
                'prof' => [
                    'title' => 'Prof.',
                    'initial' => 'D',
                    'first_name' => null,
                    'last_name' => 'Jones',
                ],
                'dr' => [
                    'title' => 'Dr.',
                    'initial' => 'B',
                    'first_name' => null,
                    'last_name' => 'Smith',
                ],
            ],
            $this->parser->parse_names('Prof. D Jones, and Dr. B Smith')->toArray()
        );

        // example 7. 'Mrs. E Smith, and Mr. B Jones'
        $this->assertEquals(
            [
                'mrs' => [
                    'title' => 'Mrs.',
                    'initial' => 'E',
                    'first_name' => null,
                    'last_name' => 'Smith',
                ],
                'mr' => [
                    'title' => 'Mr.',
                    'initial' => 'B',
                    'first_name' => null,
                    'last_name' => 'Jones',
                ],
            ],
            $this->parser->parse_names('Mrs. E Smith, and Mr. B Jones')->toArray()
        );

        // example 8. 'Mr Rupert M. Sheldrake & Mrs. Jill Purce'
        $this->assertEquals(
            [
                'mr' => [
                    'title' => 'Mr.',
                    'initial' => 'R.',
                    'first_name' => 'Rupert',
                    'last_name' => 'Sheldrake',
                ],
                'mrs' => [
                    'title' => 'Mrs.',
                    'initial' => null,
                    'first_name' => 'Jill',
                    'last_name' => 'Purce',
                ],
            ],
            $this->parser->parse_names('Mr Rupert R. Sheldrake & Mrs. Jill Purce')->toArray()
        );

        // example 9. 'Mr P Schofield, and Ms H Willoughby.'
        $this->assertEquals(
            [
                'mr' => [
                    'title' => 'Mr.',
                    'initial' => 'P',
                    'first_name' => null,
                    'last_name' => 'Schofield',
                ],
                'ms' => [
                    'title' => 'Ms.',
                    'initial' => 'H',
                    'first_name' => null,
                    'last_name' => 'Willoughby',
                ],
            ],
            $this->parser->parse_names('Mr P Schofield, and Ms H Willoughby.')->toArray()
        );

        // example 10. 'Mr Tom Staff and Mr John Doe'
        $this->assertEquals(
            [
                [
                    'title' => 'Mr.',
                    'initial' => null,
                    'first_name' => 'Tom',
                    'last_name' => 'Staff',
                ],
                [
                    'title' => 'Mr.',
                    'initial' => null,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ],
            $this->parser->parse_names('Mr Tom Staff and Mr John Doe')->toArray()
        );
    }
}
