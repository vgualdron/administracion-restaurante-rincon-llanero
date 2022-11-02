<?php
session_start();
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

require_once("../conexion.php");
require_once("../encrypted.php");
$conexion = new Conexion();

$frm = json_decode(file_get_contents('php://input'), true);
$productosPizza = [];

try {
  
  //  listar todos los posts o solo uno
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['fecha']) && isset($_GET['ventas']) && isset($_GET['controlFiscal'])) {
      $sql = $conexion->prepare("SELECT 
        DISTINCT 
        prod.prod_descripcion,
        SUM(depe.prod_cantidad) as cantidad,
        SUM(depe.prod_precio * depe.prod_cantidad) as total, 
        pedi.*,
        depe.prod_cantidad,
        depe.prod_precio
        FROM pinchetas_restaurante.producto prod
        left join pinchetas_restaurante.detallepedido depe on (depe.prod_id = prod.prod_id)
        INNER join pinchetas_restaurante.pedido pedi on (pedi.pedi_id = depe.pedi_id)
        inner join pinchetas_restaurante.estadopedido espe on (espe.espe_id = pedi.espe_id)
        WHERE espe.espe_descripcion = 'PAGO'
        AND pedi.pedi_fecha BETWEEN DATE_ADD(?, INTERVAL 0 SECOND) AND DATE_ADD(?, INTERVAL 86399 SECOND)
        GROUP BY prod.prod_id
        ORDER BY prod.prod_descripcion;");

        $sql->bindValue(1, $_GET['fecha']);
        $sql->bindValue(2, $_GET['fecha']);
        $sql->execute();
        $sql->setFetchMode(PDO::FETCH_ASSOC);
        
        header("HTTP/1.1 200 OK");
        echo json_encode( $sql->fetchAll() );
        exit(); 
    } else if (isset($_GET['fecha']) && isset($_GET['ventas'])) {
      $use = $conexion->prepare(" SELECT 
        DISTINCT 
        tipr.*
        FROM pinchetas_restaurante.producto prod
        LEFT JOIN pinchetas_restaurante.detallepedido depe on (depe.prod_id = prod.prod_id)
        INNER JOIN pinchetas_restaurante.pedido pedi on (pedi.pedi_id = depe.pedi_id)
        INNER JOIN pinchetas_restaurante.estadopedido espe on (espe.espe_id = pedi.espe_id)
        INNER JOIN pinchetas_restaurante.tipoproducto tipr on (tipr.tipr_id = prod.tipr_id)
        WHERE espe.espe_descripcion = 'PAGO'
        AND pedi.pedi_fecha BETWEEN DATE_ADD(?, INTERVAL 0 SECOND) AND DATE_ADD(?, INTERVAL 86399 SECOND)
        AND tipr.tipr_descripcion NOT LIKE '%PIZZAS%'
        GROUP BY prod.prod_id
        ORDER BY prod.prod_descripcion; ");

      $use->bindValue(1, $_GET['fecha']);
      $use->bindValue(2, $_GET['fecha']);

      $use ->execute();
      $count = $use->rowCount();
      $row = $use->fetchAll();

      $data = (object) array();
      $array = array();

      if ($count > 0) {
        foreach($row as $registro){
          $object = (object) array();
          $object->id =  $registro['tipr_id'];
          $object->descripcion = $registro['tipr_descripcion'];
          $object->orden = $registro['tipr_orden'];
          $children = array();

          $use2 = $conexion->prepare(" SELECT 
            DISTINCT 
            prod.prod_descripcion,
            SUM(depe.prod_cantidad) as cantidad,
            SUM(depe.prod_precio * depe.prod_cantidad) as total, 
            pedi.*,
            depe.prod_cantidad,
            depe.prod_precio
            FROM pinchetas_restaurante.producto prod
            LEFT JOIN pinchetas_restaurante.detallepedido depe on (depe.prod_id = prod.prod_id)
            INNER JOIN pinchetas_restaurante.pedido pedi on (pedi.pedi_id = depe.pedi_id)
            INNER JOIN pinchetas_restaurante.estadopedido espe on (espe.espe_id = pedi.espe_id)
            INNER JOIN pinchetas_restaurante.tipoproducto tipr on (tipr.tipr_id = prod.tipr_id)
            WHERE espe.espe_descripcion = 'PAGO'
            AND tipr.tipr_id = ?
            AND pedi.pedi_fecha BETWEEN DATE_ADD(?, INTERVAL 0 SECOND) AND DATE_ADD(?, INTERVAL 86399 SECOND)
            GROUP BY prod.prod_id
            ORDER BY prod.prod_descripcion; "); 

          $use2->bindValue(1, $registro['tipr_id']);
          $use2->bindValue(2, $_GET['fecha']);
          $use2->bindValue(3, $_GET['fecha']);

          $use2 ->execute();
          $count2 = $use2->rowCount();
          $row2 = $use2->fetchAll();

          if ($count2 > 0) {
            $total = 0;
            $cantidad = 0;

            foreach($row2 as $registro2){
                $objChildren = (object) array();

                $objChildren->id =  $registro2['prod_id'];
                $objChildren->idtipoproducto =  $registro2['tipr_id'];
                $objChildren->descripcion =  $registro2['prod_descripcion'];
                $objChildren->cantidad =  $registro2['cantidad'];
                $objChildren->precio =  $registro2['prod_precio'];
                $objChildren->cantidad =  $registro2['prod_cantidad'];
                $objChildren->totalT =  $registro2['total'];
                $objChildren->cantidadT =  $registro2['cantidad'];
                $objChildren->tipoPago =  $registro2['pedi_tipopago'];
                $total += $registro2['total'];
                $cantidad += $registro2['cantidad'];

                array_push($children, $objChildren);
            }

            $object->total = $total.'';
            $object->cantidad = $cantidad;
          }

          $object->productos = $children;

          array_push($array, $object);
        }
      }

      $use = $conexion->prepare(" SELECT 
        DISTINCT 
        tipr.tipr_id as idtipoproducto,
        tipr.tipr_descripcion as descripciontipoproducto,
        prod.prod_descripcion as descripcionproducto,
        tipr2.tipr_id as idtipoproducto2,
        tipr2.tipr_descripcion as descripciontipoproducto2,
        prod2.prod_descripcion as descripcionproducto2,
        pedi.*,
        depe.*
        FROM pinchetas_restaurante.detallepedido depe
        INNER JOIN pinchetas_restaurante.producto prod on (depe.prod_id = prod.prod_id)
        LEFT JOIN pinchetas_restaurante.producto prod2 on (depe.prod_id2 = prod2.prod_id)
        INNER JOIN pinchetas_restaurante.pedido pedi on (pedi.pedi_id = depe.pedi_id)
        INNER JOIN pinchetas_restaurante.estadopedido espe on (espe.espe_id = pedi.espe_id)
        INNER JOIN pinchetas_restaurante.tipoproducto tipr on (tipr.tipr_id = prod.tipr_id)
        LEFT JOIN pinchetas_restaurante.tipoproducto tipr2 on (tipr2.tipr_id = prod2.tipr_id)
        WHERE espe.espe_descripcion = 'PAGO'
        AND tipr.tipr_descripcion LIKE '%PIZZAS%'
        AND pedi.pedi_fecha BETWEEN DATE_ADD(?, INTERVAL 0 SECOND) AND DATE_ADD(?, INTERVAL 86399 SECOND) ");

      $use->bindValue(1, $_GET['fecha']);
      $use->bindValue(2, $_GET['fecha']);

      $use ->execute();
      $count = $use->rowCount();
      $row = $use->fetchAll();

      if ($count > 0) {
        $arrayProductos = array();
        foreach ($row as $registro) {
          $obj = (object) array();
          $obj->idtipoproducto = $registro['idtipoproducto'];
          $obj->descripciontipoproducto = $registro['descripciontipoproducto'];
          $obj->descripcionproducto = $registro['descripcionproducto'];
          $obj->idtipoproducto2 = $registro['idtipoproducto2'];
          $obj->descripciontipoproducto2 = $registro['descripciontipoproducto2'];
          $obj->descripcionproducto2 = $registro['descripcionproducto2'];
          $obj->precio = $registro['prod_precio'];
          $obj->cantidad = $registro['prod_cantidad'];

          array_push($arrayProductos, $obj);
        }
      } 

      $data->productosPizza = $arrayProductos;
      $data->tiposProducto = $array;

      $use = $conexion->prepare(" select
				count(pedi.pedi_id) as cantidad,
				pedi.pedi_fecha as fecha,
				sum(depe.prod_precio * depe.prod_cantidad) total 
				from pinchetas_restaurante.detallepedido depe
				inner join pinchetas_restaurante.pedido pedi on (pedi.pedi_id = depe.pedi_id)
				inner join pinchetas_restaurante.estadopedido espe on (espe.espe_id = pedi.espe_id)
				WHERE pedi.pedi_fecha BETWEEN DATE_ADD(?, INTERVAL 0 SECOND) AND DATE_ADD(?, INTERVAL 86399 SECOND)
				and espe.espe_descripcion = ?
				and pedi.pedi_tipopago = ?
				order by pedi.pedi_numerofactura asc; "); 	
                
      $use->bindValue(1, $_GET['fecha']);
      $use->bindValue(2, $_GET['fecha']);
      $use->bindValue(3, 'PAGO');
      $use->bindValue(4, 'TARJETA');
      $use ->execute();
      $row = $use->fetch();

      $totalVentasTarjeta = $row['total'] == '' ? 0 : $row['total'];
      $data->totalTarjeta = $totalVentasTarjeta.'';


      $use = $conexion->prepare(" select 
				count(pedi.pedi_id) as cantidad,
				pedi.pedi_fecha as fecha,
				sum(depe.prod_precio * depe.prod_cantidad) total 
				from pinchetas_restaurante.detallepedido depe
				inner join pinchetas_restaurante.pedido pedi on (pedi.pedi_id = depe.pedi_id)
				inner join pinchetas_restaurante.estadopedido espe on (espe.espe_id = pedi.espe_id)
				WHERE pedi.pedi_fecha BETWEEN DATE_ADD(?, INTERVAL 0 SECOND) AND DATE_ADD(?, INTERVAL 86399 SECOND)
				and espe.espe_descripcion = ?
				and pedi.pedi_tipopago = ?
				order by pedi.pedi_numerofactura asc; "); 	
                  
      $use->bindValue(1, $_GET['fecha']);
      $use->bindValue(2, $_GET['fecha']);
      $use->bindValue(3, 'PAGO');
      $use->bindValue(4, 'EFECTIVO');
      $use ->execute();
      $row = $use->fetch();

      $totalVentasEfectivo = $row['total'] == '' ? 0 : $row['total'];
      $data->totalEfectivo = $totalVentasEfectivo.'';

      header("HTTP/1.1 200 OK");
      $data->estado = $count > 0 ? "OK" : "ERROR";
      print_r(json_encode($data));
      exit(); 
    } else if (isset($_GET['fecha']) && isset($_GET['gastos'])) {

	      $sql = $conexion->prepare(" SELECT distinct
                                gast.gast_id as id,
                                gast.gast_descripcion as descripcion,
                                gast.gast_valor as valor,
                                gast.gast_fecha as fecha,
                                CONCAT(pena.pena_primernombre,' ', pena.pena_primerapellido) as nombrepersona
                                FROM pinchetas_restaurante.gasto gast
                                inner join pinchetas_general.personageneral pege on (pege.pege_id = gast.pege_idregistrador)
                                inner join pinchetas_general.personanatural pena on (pena.pege_id = pege.pege_id)
                                where gast.gast_fecha = ?
                                order by gast.gast_fecha; ;");
        $sql->bindValue(1, $_GET['fecha']);
        $sql->execute();
        $sql->setFetchMode(PDO::FETCH_ASSOC);
        header("HTTP/1.1 200 OK");
        echo json_encode( $sql->fetchAll() );
        exit(); 
     }
  }

} catch (Exception $e) {
    echo 'Excepción capturada: ', $e->getMessage(), "\n";
}

//En caso de que ninguna de las opciones anteriores se haya ejecutado
// header("HTTP/1.1 400 Bad Request");

?>
