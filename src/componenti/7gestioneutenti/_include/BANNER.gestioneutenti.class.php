<?php
/*

    class to handle users, extends GestioneUtenti

*/

class BANNER_GestioneUtenti extends GestioneUtenti
{

    /**
     * show users list using parent object
     * 
     * @param string $combotipo
     * @param string $combotiporeset
     * @param string $keyword
     * @param array $params
     * 
     * @return string 
     */
	function elencoUtenti($combotipo="",$combotiporeset="",$keyword="",$params=array()) {

        // define fields and query to build the grid
        $params['fields']="name,username,de_label,de_email,de_nome,fl_attivo";
        $params['labels']="{Name},{Username},{Profile},{Email address},{Client},{Status}";
        $params['query'] ="SELECT distinct CONCAT(cognome,' ',nome) as name,".DB_PREFIX."frw_utenti.id,".
            DB_PREFIX."frw_utenti.username,".DB_PREFIX."frw_profili.de_label,fl_attivo,password,de_email,de_nome from ".DB_PREFIX."frw_utenti join ".DB_PREFIX."frw_profili on ".DB_PREFIX."frw_utenti.cd_profilo=".DB_PREFIX."frw_profili.id_profilo 
            left outer join ".DB_PREFIX."frw_extrauserdata on cd_user=id 
            left outer join ".DB_PREFIX."7banner_clienti on cd_utente=id";

        // call parent object
		return parent::elencoUtenti($combotipo,$combotiporeset,$keyword,$params);
	}



	/**
     * Show user detail form, used both for insert and update.
     * It uses the parent object.
     * 
     * @param string $id
     * @param array $params
     * 
     * @return string
     */
	function getDettaglioNew($id="",$params=array()) {
        global $session;

        $params['template'] = 'template/BANNER_dettaglio_new.html';

        $u = new user($session->get("idutente"),$this->MAX_USER_LEVEL);

        $onlybasic = $u->getManualPermission($id,"BANNER_LIMITTOBBASIC");
        $fl_onlybasic=new checkbox("fl_onlybasic",1,$onlybasic==1);
        $fl_onlybasic->obbligatorio=0;
        $fl_onlybasic->label="'{Restrictions}'";

        $autoapprove = $u->getManualPermission($id,"BANNER_AUTOAPPROVEPENDING");
        $fl_autoapprove=new checkbox("fl_autoapprove",1,$autoapprove==1);
        $fl_autoapprove->obbligatorio=0;
        $fl_autoapprove->label="'{Restrictions}'";
        
        $params['fieldsObjects'] = array( $fl_onlybasic, $fl_autoapprove );

        return parent::getDettaglioNew($id,$params);

	}


    /**
     * Update and insert for the user data using parent <object data="
     * 
     * @param array $arDati
     * 
     * @return string
     */
	function updateAndInsert($arDati) {

        $result = parent::updateAndInsert($arDati);

        if(stristr($result,"ok|")) {

            // get the id
            $id = (integer)str_replace( "|","",stristr( $result, "|")) ;
            
            if($id > 0) {
                $u=new user();
                $u->MAX_USER_LEVEL=$this->MAX_USER_LEVEL;

                if (!isset($arDati["fl_onlybasic"])) $arDati["fl_onlybasic"]="0";
                if($arDati["fl_onlybasic"]=="1"){
                    $u->setManualPermission($id,"BANNER_LIMITTOBBASIC");
                } else {
                    $u->removeManualPermission($id,"BANNER_LIMITTOBBASIC");
                }
                if (!isset($arDati["fl_autoapprove"])) $arDati["fl_autoapprove"]="0";
                if($arDati["fl_autoapprove"]=="1"){
                    $u->setManualPermission($id,"BANNER_AUTOAPPROVEPENDING");
                } else {
                    $u->removeManualPermission($id,"BANNER_AUTOAPPROVEPENDING");
                }

            }
            
        }

		return $result;
	}


    /**
     * Delete selected users, delete using parent method and then delete extra records
     * 
     * @param array $dati
     * 
     * @return string '' --> ok | '0' --> can't
     */
	function eliminaSelezionati($dati) {
		global $conn;

        $result = parent::eliminaSelezionati($dati);
		if ($result =="") {
            // extra deletes
            $p=$dati['gridcheck'];
            for ($i=0;$i<count($p);$i++) {
                 $sql="DELETE FROM ".DB_PREFIX."7banner_clienti where cd_utente='".(integer)$p[$i]."'";
                 $conn->query($sql) or trigger_error($conn->error."sql='$sql'<br>");
            }
        }
		return $result;
	}


}
