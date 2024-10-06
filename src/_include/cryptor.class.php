<?php

/*

Usage example:

 $cr64 = new Cryptor();
 $e = $cr64->crypta($keys);
 echo "Crypted information = ".$e."<br>";
 $d = $cr64->decrypta($e);
 echo "Decrypted information = ".$d."<br>";

*/

class Cryptor{
   var $k;
   function __construct($m='fWkv101g1ul10p0n5'){
      $this->k = $m;
   }
   function ed($t) { 
      $r = md5($this->k); 
      $c=0; 
      $v = ""; 
      for ($i=0;$i<strlen($t);$i++) { 
         if ($c==strlen($r)) $c=0;
         $v.= substr($t,$i,1) ^ substr($r,$c,1); 
         $c++; 
      } 
      return $v; 
   } 
   function crypta($t){ 
      $r = md5(10); 
      $c=0; 
      $v = ""; 
      for ($i=0;$i<strlen($t);$i++){ 
         if ($c==strlen($r)) $c=0; 
         $v.= substr($r,$c,1) . 
             (substr($t,$i,1) ^ substr($r,$c,1)); 
         $c++; 
      } 
      return base64_encode($this->ed($v)); 
   } 
   function decrypta($t) { 
      $t = $this->ed(base64_decode($t)); 
      $v = ""; 
      for ($i=0;$i<strlen($t);$i++){ 
         $md5 = substr($t,$i,1); 
         $i++; 
         $v.= (substr($t,$i,1) ^ $md5); 
      } 
      return $v; 
   } 
}
?>