<?php
class PayForm {

    static function getHashFromArray($arrayList, $key) {
	ksort($arrayList);
     $all = '';
     $salt=trim(get_option('payten_merchant_key'));
     $postdata=$arrayList;
        foreach ($postdata as $name => $value) {
            $all .= $name."=".$value."~";
        }
        
        $all = substr($all, 0, -1);
        $all .= $salt;
         return strtoupper(hash('sha256', $all));
    }
	
}