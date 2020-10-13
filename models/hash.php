<?php
 function hash_generator(){
    $date=date("y-m-d");
    $sw="ajay";
    $hash=base64_encode($date.$sw);

    return $hash;

}

 function hash_checker($key){
    $a=hash_generator();
    if($key==$a){
        return true;
    }
    else{
        return false;
    }

}
?>
