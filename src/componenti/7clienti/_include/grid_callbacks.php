<?php

/*

    function for grid list

*/

function show_client_value($v) {
	return ($v > 0 ? numberf($v,2).MONEY : "");
}

function show_ecpm($v){
    return ($v > 0 ? numberf($v,4).MONEY : "");
}
