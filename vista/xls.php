<?php 
echo $_POST['tabla'];
header("Pragma: public");
header("Expires: 0");
$filename = "nombreArchivoQueDescarga.xls";
// header("Content-type: application/x-msdownload");
header("Content-type: application/vnd.ms-excel"); //Omitir en producción
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

?>
