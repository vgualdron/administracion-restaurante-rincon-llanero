<?php
session_start();
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

require_once("../conexion.php");
require_once("../encrypted.php");
$conexion = new Conexion();

try {
  
  //  listar todos los posts o solo uno
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      $registradopor = openCypher('decrypt', $_GET['token']);
      if (isset($_GET['isdelete'])) {
        $sql = $conexion->prepare(" SELECT 
          DISTINCT 
          pedi.pedi_id as idPedido,
          pedi.pedi_fecha as fecha,
          depe.depe_id as idDetallePedido,
          prod.prod_descripcion descripcion,
          depe.prod_cantidad as cantidad,
          depe.prod_precio as precio,
          (depe.prod_precio * depe.prod_cantidad) as total
          FROM pinchetas_restaurante.detallepedido depe
          INNER join pinchetas_restaurante.producto prod on (depe.prod_id = prod.prod_id)
          INNER join pinchetas_restaurante.pedido pedi on (pedi.pedi_id = depe.pedi_id)
          INNER join pinchetas_restaurante.estadopedido espe on (espe.espe_id = pedi.espe_id)
          WHERE espe.espe_descripcion = 'PAGO'
          AND pedi.pedi_tipopago = 'EFECTIVO'
          AND pedi.pedi_fecha BETWEEN DATE_ADD(?, INTERVAL 0 SECOND) AND DATE_ADD(?, INTERVAL 86399 SECOND)
          AND (SELECT count(depe.depe_id) FROM pinchetas_restaurante.detallepedido depe WHERE depe.pedi_id = pedi.pedi_id GROUP BY pedi.pedi_id) >= 2
          ORDER BY pedi.pedi_id asc, depe.depe_id asc;");
                    							
        $sql->bindValue(1, $_GET['fechaInicio']);
        $sql->bindValue(2, $_GET['fechaFin']);
        $sql->execute();
        header("HTTP/1.1 200 OK");
        $result = $sql->fetchAll();

        if ($result == false) {
          $data = (object) array();
          $data->mensaje = "No se encontró el registro...";
          header("HTTP/1.1 400 Bad Request");
          echo json_encode( $data );
          exit();
        } else {
          echo json_encode($result);
          exit();
        }
  	  }
  } else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
      $input = $_GET;
      $id = $input['id'];
      $registradopor = openCypher('decrypt', $input['token']);
      $date = date("Y-m-d H:i:s");
      
      $sql = "CALL procedimiento_eliminar_detallepedido(?, ?); ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $id);
      $sql->bindValue(2, $registradopor);
      $result = $sql->execute();
      if($result) {
        $output['mensaje'] = "Eliminado con éxito";
        header("HTTP/1.1 200 OK");
        echo json_encode($output);
        exit();
  	  } else {
        $output['mensaje'] = "Error eliminando";
        header("HTTP/1.1 400 Bad Request");
        echo json_encode($output);
        exit();
  	  }
  }

} catch (Exception $e) {
    echo 'Excepción capturada: ', $e->getMessage(), "\n";
}

//En caso de que ninguna de las opciones anteriores se haya ejecutado
// header("HTTP/1.1 400 Bad Request");

?>