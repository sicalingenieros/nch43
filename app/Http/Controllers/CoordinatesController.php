<?php

namespace App\Http\Controllers;

use App\Models\Coordinate;
use App\Models\Procedure;
use Illuminate\Http\Request;

class CoordinatesController extends Controller
{
    public function nch43()
    {
        $lote = 8;
        $samples = 5;
        $row = 221;
        $column = 1;

        $procedimiento = Procedure::where('max','>=',$lote)->where('min','<=',$lote)->first();
        $out = null;
        $columnas_a_usar = round($procedimiento->digits/2);

        $salida = [];
        $valido=0;
        if($procedimiento->move == "utd"){
            while ($valido < $samples) { 
                for ($i=1; $i < $columnas_a_usar; $i++) { 
                    $out =  Coordinate::where('row',$row)->where('column', $column)->first()->number.
                            Coordinate::where('row',$row)->where('column', $column+$i)->first()->number;
                }     
                $number = intval(filter_var(str_split($out, $procedimiento->digits)[0], FILTER_SANITIZE_NUMBER_INT));
                $numero = collect();
                $this->operations($procedimiento, $number, $numero, $lote, $row, $column);
                if($this->alreadyTake($numero, $salida))
                    $numero->put('valido', false);
                if($numero['valido'])
                    $valido++;
                

                $row++;
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

                        $this->operations($procedimiento, $digit, $numero, $lote, $row, $column);
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
                    $this->operations($procedimiento, $number, $numero, $lote, $row, $column);
                    
                    if($this->alreadyTake($numero, $salida))
                        $numero->put('valido', false);
                    if($numero['valido'])
                        $valido++;
                    $column++;
                    $out = "";
                    $salida[] = $numero;
                }

            }
            
        }
        //intval(filter_var($number, FILTER_SANITIZE_NUMBER_INT))
        return $salida;
    }

    private function alreadyTake($numero, $salidas)
    {
        foreach($salidas as $salida){
            if ($salida['valor_final'] == $numero['valor_final'])             
                return true;
        }
            return false;
        
    }

    private function operations($procedimiento, $number, $numero, $lote, $row, $column){
        $numero->put('valor_original', $number);
        $numero->put('fila', $row);
        $numero->put('columna', $column);

        if($number <= $lote){
            $numero->put('valor_final', $number);
            $numero->put('valido', true);
            $numero->put('operación', 'NA');
        }
        if($number > $lote){
            if($procedimiento->divider <> 0){
                $numero->put('valor_final', $number%$procedimiento->divider);
                $numero->put('valido', $number%$procedimiento->divider<=$lote);
                $numero->put('operación', floor($number/$procedimiento->divider).' x '.$procedimiento->divider.' + '.$number%$procedimiento->divider.' = '.$number);
            }else{
                $numero->put('valor_final', $number);
                $numero->put('valido', $number<=$lote);
                $numero->put('operación', 'NA');
            }
        }
    }
}
