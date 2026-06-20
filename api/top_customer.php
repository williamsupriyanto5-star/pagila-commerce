<?php

include("../config/db.php");

$sql="

SELECT

customer_key,

customer_lifetime_value

FROM fact_customer_activity

ORDER BY customer_lifetime_value DESC

LIMIT 10

";

$result=pg_query($conn,$sql);

$data=[];

while($row=pg_fetch_assoc($result)){

$data[]=$row;

}

header("Content-Type:application/json");

echo json_encode($data);

?>