<?php
$res = $_REQUEST;
$totamt = '';
$totamt = getMortgagevalues($res['amount'],$res['interestrate'],$res['termsofyears'],$res['term']);

        if($res['language'] == 'fr'){
            
            $resulte  = "R&#233;sultats";
            $resulamt = "Montant de l'hypoth&#233;que";
            $resulrat = "Taux d'int&#233;r&#234;t(%)";
            $resulter = "Dur&#233;e du pr&#234;t hypoth&#233;caire";
            $monthpay = "Mensualit&#233;s"; 
           

        }else if($res['language'] == 'es'){
            
           $resulte  = "Resultados";
           $resulamt = "Importe de la hipoteca";
           $resulrat = "Tasa de inter&#233;s (%)";
           $resulter = "Plazo de la hipoteca";
           $monthpay = "Los pagos mensuales"; 
          

        }else if($res['language'] == 'en'){

           $resulte  = "Results";
           $resulamt = "Mortgage amount";
           $resulrat = "Interest Rate(%)";
           $resulter = "Mortgage Term";
           $monthpay = "Monthly Payments"; 
        

        }else if($res['language'] == 'pt'){

           $resulte  = "Resultados";
           $resulamt = "Valor da hipoteca";
           $resulrat = "Taxa de juros (%)";
           $resulter = "Prazo de hipoteca";
           $monthpay = "Pagamentos mensais"; 
        

        }

function getMortgagevalues($amount, $interest, $term, $option){
          
        if($option == '1'){
        $payments = $term; 
        }else{
        $payments = $term * 12;
        }
      
        $percentage = $interest / 100 / 12; //Monthly interest rate
        return $amount * ( $percentage * pow(1 + $percentage, $payments) ) / ( pow(1 + $percentage, $payments) - 1);

}

?>


<table border="0" cellspacing="0" cellpadding="0" class="" align="center">
     <!-- Loop through the entries that were provided to us by the controller -->
     <tr>
            <td>
                <fieldset>
                    <legend><b><?php echo $resulte; ?></b></legend>
                    <hr></hr>
                        <table border="0" cellspacing="10" cellpadding="0" >
                            <tr>
                                <td><div class="RESearchLabelStatus"><?php echo $resulamt; ?></div></td>
                                <td><div class="RESearchColon">: </div></td>
                                <td><div class="RESearchElementStatus"><?php echo $res['amount'];?></div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td><div class="RESearchLabelStatus"><?php echo $resulrat; ?></div></td>
                                <td><div class="RESearchColon">: </div></td>
                                <td><div class="RESearchElementStatus"><?php echo $res['interestrate'];?></div>
                                </td>
                            </tr>
                            
                            <tr>
                                <td><div class="RESearchLabelStatus"><?php echo $resulter; ?></div></td>
                                <td><div class="RESearchColon">: </div></td>
                                <td><div class="RESearchElementStatus"><?php echo $res['termsofyears'];?></div>
                                </td>
                            </tr>
                            <tr>
                                <td><div class="RESearchLabelStatus">
                                <?php  echo $monthpay; ?>
                                </div></td>
                                <td><div class="RESearchColon">: </div></td>
                                <td><div class="RESearchElementStatus"><?php echo round($totamt,2);?></div>
                                </td>
                            </tr> 
                            <!-- <tr>
                                <td><div class="RESearchLabelStatus"><?php //_e("[:fr]Date de dÃƒÂ©but[:en]Start Date[:es]Fecha de inicio") ?></div></td>
                                <td><div class="RESearchColon">:</div></td>
                                <td><div class="RESearchElementStatus"><?php //echo $res['departure_date'];?></div>
                                    
                                </td>
                            </tr> -->
                        </table>
                   
                </fieldset>
            </td>
            
        </tr>
        
  </table>

