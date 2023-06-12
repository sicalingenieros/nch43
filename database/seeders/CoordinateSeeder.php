<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CoordinateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $csvFile = fopen(public_path("nch43.csv"), "r");
 
        $coordinates = collect();
        while (($data = fgetcsv($csvFile, null, ";")) !== FALSE) {
           $coordinates->push($data);
        }
        fclose($csvFile);

        foreach($coordinates as $key => $coordinate){
            $row = $key+1;

            foreach($coordinate as $key_2 => $number){


                \DB::table('coordinates')->insert([
                    'row' => $row,
                    'column' => $key_2+1,
                    'number' => intval(filter_var($number, FILTER_SANITIZE_NUMBER_INT))
                ]);
            }
        }
        
    }
}
