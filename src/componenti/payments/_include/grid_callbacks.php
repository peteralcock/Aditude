<?php

function show_payment_value($v) {
	return ($v > 0 ? numberf($v,2).MONEY : "");
}

?>