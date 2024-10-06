<?php
/*

	class to handle own data, extends MioProfilo

*/

class BANNER_MioProfilo extends Mioprofilo
{

	function __construct () {
		parent::__construct();
	}

    /**
     * show user logged own details. defines the additional fields to show
     * and implements call to parent object same function
     * 
     * @param array $params
     * 
     * @return string 0 | 1 | 2
     */
	function getDettaglio( $params = array() ) {

		global $session,$root;
        
        // define template
        // --------------------------------------------- 
        $params['template'] = 'template/BANNER_dettaglio.html';

        // retrieves details for advertiser user
        // ---------------------------------------------
        $clienteDati = execute_row("select * from ".DB_PREFIX."7banner_clienti where cd_utente='". $session->get("idutente") ."'");

        if(!isset($clienteDati['id_cliente'])) {
            $clienteDati['id_cliente']="";
            $clienteDati['de_nome']="";
            $clienteDati['de_address']="";
        }
        
        $id_cliente = new hidden("id_cliente",$clienteDati['id_cliente']);
    
        $de_nome = new testo("de_nome",$clienteDati["de_nome"],50,50);
        $de_nome->obbligatorio= $session->get("idprofilo")==5 ? 1 : 0;
        $de_nome->label="'{Company name}'";

        $de_address = new areatesto("de_address",(($clienteDati["de_address"])),5,80);
        $de_address->obbligatorio=0;
        $de_address->label="'{Address}'";


        // retrieves details for webmaster user
        // ---------------------------------------------
        $extraUserData = execute_row("select de_payment_details from ".DB_PREFIX."frw_extrauserdata where cd_user='". $session->get("idutente") ."'");
        $de_payment_details = new areatesto("de_payment_details",(($extraUserData["de_payment_details"])),5,80);
        $de_payment_details->obbligatorio=0;
        $de_payment_details->label="'{Payment details}'";
       
        // send fields to parent object
        // ---------------------------------------------
        $params['fieldsObjects'] = array( $id_cliente, $de_nome, $de_address, $de_payment_details );


        return parent::getDettaglio($params);
	}


    /**
     * handles the form for sign in, using the parent object and adding a field for profile
     * 
     * @param array $params
     * 
     * @return string
     */
	function getDettaglioSignIn($params=array()) {
		global $session;

        // define template
        $params['template'] = 'template/BANNER_signin.html';

        // define fields
        $cd_profilo = new optionlist("cd_profilo",5,array(
            5=>"{Advertiser}",
            10=>"{Webmaster}"
        ));
       
        // send fields to parent object
        $params['fieldsObjects'] = array( $cd_profilo );

        return parent::getDettaglioSignIn($params);
	}

    /**
     * handle the updates posted by the dettaglio form using the parent object
     * and implementing the update of the additional fields.
     * 
     * @param array $arDati
     * @param array $files
     * 
     * @return string
     */
	function update($arDati, $files) {
        global $session, $conn;

        // update with parent
        $result = parent::update($arDati, $files);

        // update additional fields
        if($result == "") {
            
            if($session->get("idprofilo")==5) {
          
                if ($arDati["id_cliente"]!="") {
                    //Modify some data on client table
                    $sql="UPDATE ".DB_PREFIX."7banner_clienti set de_nome='##de_nome##',de_address='##de_address##' where id_cliente='##id_cliente##'";
                    $sql= str_replace("##de_nome##",$arDati["de_nome"],$sql);
                    $sql= str_replace("##de_address##",$arDati["de_address"],$sql);
                    $sql= str_replace("##id_cliente##",$arDati["id_cliente"],$sql);
                    $conn->query($sql) or die($conn->error."sql='$sql'<br>");

                } else {
                    //create record on client table if not exists
                    $sql="INSERT into ".DB_PREFIX."7banner_clienti (de_nome,de_address,cd_utente) values('##de_nome##','##de_address##','##cd_utente##')";
                    $sql= str_replace("##de_nome##",$arDati["de_nome"],$sql);
                    $sql= str_replace("##de_address##",$arDati["de_address"],$sql);
                    $sql= str_replace("##cd_utente##",$session->get("idutente"),$sql);
                    $conn->query($sql) or die($conn->error."sql='$sql'<br>");
                }
                
            }
            
            if($session->get("idprofilo")==10) {
                // update payment details for webmaster

                $sql="UPDATE ".DB_PREFIX."frw_extrauserdata set de_payment_details='##de_payment_details##' where cd_user='##id_user##'";
                $sql= str_replace("##de_payment_details##",$arDati["de_payment_details"],$sql);
                $sql= str_replace("##id_user##", $session->get("idutente"),$sql);              
                $conn->query($sql) or die($conn->error."sql='$sql'<br>");

            }

        }

		return $result;
	}



}
