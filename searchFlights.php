<?php
/**
 * Created by PhpStorm.
 * User: ruben
 * Date: 23/01/2018
 * Time: 15:05
 */

require('momondo.php');
require ('MySQL.php');
if($_GET){
    if(isset($_GET['teste'])){
        extractDataFromHTML();
    }elseif(isset($_GET['teste'])){
        extractDataFromHTML();
    }
}

function results($aeroP, $aeroD, $dataPartida, $dataChegada, $quantosAdultos, $quantasPesquisasQueremosVer){

    $momondo = new Momondo();
//-----------------------------------------------------------------------------------------------//
// Search (include search_url && search_results)
    $results = $momondo->search($aeroP, $aeroD, $dataPartida, $dataChegada, $quantosAdultos, 'ECO', array());    // Children: 6 && 10 years
//-----------------------------------------------------------------------------------------------//

    echo "<table border='1'><tr><th>Index</th><th>Aeroporto Partida</th><th>Aeroporto Chegada</th><th>Data Partida</th><th>Data Chegada</th><th>Preço</th></tr>";

    $quantosResultados = filterResultsFromMomondoAPI($results,$aeroP, $aeroD, $quantasPesquisasQueremosVer);

    echo "</table><br><br>Pesquisas totais =======> ".$quantosResultados."<br>";

    insertingDataIntoDatabase($aeroP,$aeroD, $dataPartida,$dataChegada);

}//results

function insertingDataIntoDatabase($aeroP,$aeroD, $dataPartida,$dataChegada){
    $dataPesquisa = date("Y/m/d");
    $wd = date("l");
    $dataP = $dataPesquisa.", ".$wd;
    dbInsert($aeroP,$aeroD, $dataPartida,$dataChegada,$dataP);
}//insertingDataIntoDatabase
//______________________________________________Form Data____________________________________________//
function extractDataFromHTML(){
    $aeroP = $_GET['aeroP'];
    $aeroD = $_GET['aeroD'];
    echo "Aeroportos : ".$aeroP." => ".$aeroD."<br>";

    $dataPartida = $_GET['dataP'];
    $dataChegada = $_GET['dataV'];
    $frase = "Datas : ".$dataPartida." ===> ".$dataChegada;
    echo $frase."<br>";

    $quantosAdultos = $_GET['passageirosA'];
    echo "Nº de Adultos : ".$quantosAdultos."<br>";

    $quantasPesquisasQueremosVer = $_GET['quantasPesquisasQuer'];
    echo "Queremos ver : ".$quantasPesquisasQueremosVer." Voos"."<br>";

    results($aeroP, $aeroD, $dataPartida, $dataChegada, $quantosAdultos, $quantasPesquisasQueremosVer);

}//extractDataFromHTML
//______________________________________________Form Data____________________________________________//

function filterResultsFromMomondoAPI($results, $aeroP, $aeroD, $quantasPesquisasQueremosVer){

    $idx = 0;
    $quantosResultados = 0;
    $resultArrayEspecifico = $results[2];
    foreach ($resultArrayEspecifico["Offers"] as $offers) {
        $indexDeVoo = $offers["FlightIndex"];
        //$classeBilhete = $offers["TicketClassIndex"];
        $precoTotal = $offers["TotalPriceEUR"];

        foreach ($resultArrayEspecifico["Flights"] as $osVoos) {

            if($resultArrayEspecifico["Flights"][$indexDeVoo]!=null){

                $oVooQueQueroFiltrar = $resultArrayEspecifico["Flights"][$indexDeVoo];
                if ($oVooQueQueroFiltrar == $osVoos) {

                    $indexIda = $osVoos["SegmentIndexes"]["0"];
                    $indexVolta = $osVoos["SegmentIndexes"]["1"];
                    if($indexIda!=null && $indexVolta!=null){

                        foreach ($resultArrayEspecifico["Segments"] as $oSegmento) {

                            $segmentoDeIda = $resultArrayEspecifico["Segments"][$indexIda];
                            $segmentoDeVolta = $resultArrayEspecifico["Segments"][$indexVolta];
                            if ($segmentoDeIda === $oSegmento || $segmentoDeVolta === $oSegmento) {

                                $indexDeLegIda = $oSegmento["LegIndexes"][0];
                                $indexDeLegVolta = $oSegmento["LegIndexes"][0];
                                $arrayDeDados = $resultArrayEspecifico["Legs"];
                                if($indexDeLegIda!=null && $indexDeLegVolta!=null && $arrayDeDados!=null){
                                    foreach ($arrayDeDados as $oInfo) {

                                        $arrayDeDadosEspecificoDeIda = $resultArrayEspecifico["Legs"][$indexDeLegIda];
                                        $arrayDeDadosEspecificoDeVolta = $resultArrayEspecifico["Legs"][$indexDeLegVolta];
                                        if ($arrayDeDadosEspecificoDeIda === $oInfo || $arrayDeDadosEspecificoDeVolta === $oInfo) {
                                            $dataPartida = $oInfo["Departure"];
                                            $dataChegada = $oInfo["Arrival"];
                                            if ($idx < ($quantasPesquisasQueremosVer*2)) {

                                                //$resultado = $idx . " ==> " . $indexDeVoo . " => " . $classeBilhete . " => " . $precoTotal . " => " . $dataPartida . " => " . $dataChegada . "\n";
                                                $restoZeroParaTrocarIdaVolta = $idx+1;
                                                if($restoZeroParaTrocarIdaVolta%2 ){
                                                    echo "<tr><td>" . $indexDeVoo . "</td><td>" . $aeroD . "</td><td>" . $aeroP . "</td><td>" . $dataPartida . '</td><td>' . $dataChegada . '</td><td>' . $precoTotal . "€ </td></tr>" . PHP_EOL;
                                                }else{
                                                    echo "<tr><td>" . $indexDeVoo . "</td><td>" . $aeroP . "</td><td>" . $aeroD . "</td><td>" . $dataPartida . '</td><td>' . $dataChegada . '</td><td>' . $precoTotal . "€ </td></tr>" . PHP_EOL;
                                                }

                                                $idx++;
                                            }//if filtra os 20
                                            $quantosResultados++;
                                        }//if filtra os dados de ida e vinda (datas)
                                    }//foreach percorrer o array de dados
                                }//if filtra dados nulos
                            }//if  filtra os segmentos de ida e volta
                        }//foreach percorrer o array dos segmentos
                    }//if filtra dados nulos
                }//if filtrar os voos que quero
            }//if filtrar se o index é nulo
        }//foreach percorrer o array dos voos
    }//foreach percorrer todas as ofertas disponiveis
    return $quantosResultados;
}//filterResultsFromMomondoAPI