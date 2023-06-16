<?php

namespace App\Http\Controllers;

use App\Models\Coordinate;
use App\Models\Procedure;
use Illuminate\Http\Request;

class CoordinatesController extends Controller
{
    public function nch43($lote, $samples, $row, $column)
    {
        
        $init_row = [1, 36, 71, 106, 141, 176, 211, 246];
        $limit_row = [35, 70, 105, 140, 175, 210, 245, 250];
        $limit_column = 20;

        $initial_coordinate = $row.'/'.$column;

        $procedimiento = Procedure::where('max','>=',$lote)->where('min','<=',$lote)->first();

        $columnas_a_usar = round($procedimiento->digits/2);

        $out = null;
        $salida = [];
        $valido=0;


        if($procedimiento->move == "utd"){
            while ($valido < $samples) { 

                //En la variable $out quedan unidos los pares de números

                for ($i=0; $i < $columnas_a_usar; $i++) { 
                    if(Coordinate::where('row',$row)->where('column', $column + $i)->count() == 0)
                        dd($row, $column);
                    $out .=  Coordinate::where('row',$row)->where('column', $column + $i)->first()->number;
                } 


                $numero = collect();
                $this->operations($procedimiento, $out, $numero, $lote, $row, $column, $columnas_a_usar);

 
                if($this->alreadyTake($numero, $salida))
                    $numero->put('valido', false);
                if($numero['valido'])
                    $valido++;
                
                if(in_array($row, $limit_row)){ //si la fila actual es la última de la pagina entro 
                    if($column+$columnas_a_usar - 1 >= 20){
                        $row++;
                        $column = 1;
                    }else{
                        $row= $init_row[array_search($row, $limit_row)]; //seteo la fila con la inicial de su misma pagina
                        $column = $column+$columnas_a_usar;
                    }
                }else{
                    $row++;
                }

                if($row > 250)
                    $row = 1;

                if($column+$columnas_a_usar - 1 >= 20)
                    $column = 1;

                $out = "";
                $salida[] = $numero;      
            }
        }else{
            if($procedimiento->digits == 1){
                while ($valido < $samples) {
                    foreach(str_split(Coordinate::where('row',$row)->where('column', $column)->first()->number, $procedimiento->digits) as $digit){
                        if($valido >= $samples)
                            break;
                        $numero = collect();

                        $this->operations($procedimiento, $digit, $numero, $lote, $row, $column, $columnas_a_usar);
                        if($this->alreadyTake($numero, $salida))
                            $numero->put('valido', false);
                        if($numero['valido'])
                            $valido++;
                        $column++;
                        $salida[] = $numero;
                    }

                }
            }elseif($procedimiento->digits == 2){
                
                while ($valido < $samples) { 
                    $number = intval(filter_var(Coordinate::where('row',$row)->where('column', $column)->first()->number, FILTER_SANITIZE_NUMBER_INT));
                    $numero = collect();
                    $this->operations($procedimiento, $number, $numero, $lote, $row, $column, $columnas_a_usar);
                    
                    if($this->alreadyTake($numero, $salida))
                        $numero->put('valido', false);
                    if($numero['valido'])
                        $valido++;

                    if($column == $limit_column){
                        $row++;
                        $column = 1; 
                    }else{
                        $column++;
                    }

                    if($row > 250)
                        $row = 1;
                    
                    $out = "";
                    $salida[] = $numero;
                }

            }
            
        }
        //intval(filter_var($number, FILTER_SANITIZE_NUMBER_INT))

        return view('welcome', [
            'data' => json_encode($salida),
            'lote' => $lote,
            'muestras' => $samples,
            'inicial' => $initial_coordinate
        ]);
    }

    private function alreadyTake($numero, $salidas)
    {
        foreach($salidas as $salida){
            if ($salida['valor_final'] == $numero['valor_final'])             
                return true;
        }
            return false;
        
    }

    private function operations($procedimiento, $number, $numero, $lote, $row, $column, $columnas_a_usar){
        $numero->put('fila', $row);
        
        $count = 0;
        $column_to_show = '';
        while($count < $columnas_a_usar ){
            $column_to_show .= $column+$count.' | ';
            $count++;
        }
        $column_to_show = substr($column_to_show, 0, -2);
        

        //$numero->put('columna', $column+$columnas_a_usar-1);
        $numero->put('columna', $column_to_show);

        $valor_original = str_split($number, $procedimiento->digits)[0];

        $numero->put('valor_original', str_split($number, $procedimiento->digits)[0]);
        $number = intval(filter_var(str_split($number, $procedimiento->digits)[0], FILTER_SANITIZE_NUMBER_INT));

        if($this->itsZeros($number, $procedimiento)){
            $numero->put('valor_final', $procedimiento->max);
            $numero->put('valido', $procedimiento->max<=$lote);
            $numero->put('operación', "NA");
            $numero->put('comentario', "Art. 8. El ". $valor_original ." de la tabla se leerá como ".$procedimiento->max);
        }else{
            if($procedimiento->divider <> 0){
                if($number%$procedimiento->divider == 0){
                    $numero->put('valor_final', $procedimiento->max);
                    $numero->put('valido', $procedimiento->max<=$lote);
                    $numero->put('operación', floor($number/$procedimiento->divider).' x '.$procedimiento->divider.' + '.$number%$procedimiento->divider.' = '.$number);
                    $numero->put('comentario', "Art. 8. El resto 0 de la tabla se leerá como ".$procedimiento->max);
                    if($number%$procedimiento->divider>$lote)
                        $numero->put('comentario','Valor final mayor al lote.');
                }else{
                    $numero->put('valor_final', $number%$procedimiento->divider);
                    $numero->put('valido', $number%$procedimiento->divider<=$lote);
                    if($number%$procedimiento->divider>$lote)
                        $numero->put('comentario', 'Valor final mayor al lote.');
                    $numero->put('operación', floor($number/$procedimiento->divider).' x '.$procedimiento->divider.' + '.$number%$procedimiento->divider.' = '.$number);
                    if($number <= $lote)
                        $numero->put('operación', 'NA');
                }
            }else{
                $numero->put('valor_final', $number);
                $numero->put('valido', $number<=$lote);
                if($number>$lote)
                        $numero->put('comentario', 'Valor final mayor al lote.');
                $numero->put('operación', 'NA');
            }
            
        }

    }

    private function itsZeros($numero, $reglas){
        return (str_split($numero, $reglas->digits)[0] == str_repeat("0", $reglas->digits));
    }
}
