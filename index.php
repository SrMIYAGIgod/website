<?php
session_start();

// Definir los precios de los productos
$precios = array(
    'tacos' => 3000,
    'bebidas' => 5000
);

// Función para calcular el valor total de un pedido
function calcular_valor_pedido($pedido) {
    global $precios;
    $total = 0;
    foreach ($pedido['productos'] as $producto => $cantidad) {
        $total += $precios[$producto] * $cantidad;
    }
    return $total;
}

// Función para generar un número de pedido único
function generar_numero_pedido() {
    return uniqid();
}

// Función para eliminar un pedido
function eliminar_pedido($numero_pedido) {
    if(isset($_SESSION['usuarios'][$numero_pedido])){
        unset($_SESSION['usuarios'][$numero_pedido]);
        return true;
    } else {
        return false;
    }
}

if(isset($_POST['upd'])){
    $_SESSION['usuarios'][$_POST['key']] = array(
        'nombre' => $_POST['nombre'],
        'cedula' => $_POST['cedula'],
        'tacos' => $_POST['tacos'],
        'bebidas' => $_POST['bebidas']
    ); 
}

if(isset($_POST['del'])){
    unset($_SESSION['usuarios'][$_POST['key']]);
}

if(isset($_POST['add'])){

    $numero_pedido = generar_numero_pedido();
    $productos_pedido = array();
    
    if ($_POST['tacos'] > 0) {
        $productos_pedido['tacos'] = $_POST['tacos'];
    }
    if ($_POST['bebidas'] > 0) {
        $productos_pedido['bebidas'] = $_POST['bebidas'];
    }
    
    // Guardar la imagen del cliente
    if ($_FILES['imagen_cliente']['error'] === UPLOAD_ERR_OK) {
        $nombre_imagen = $_FILES['imagen_cliente']['name'];
        move_uploaded_file($_FILES['imagen_cliente']['tmp_name'], 'uploads/' . $nombre_imagen);
    } else {
        $nombre_imagen = null;
    }
    
    // Guardar el pedido en la sesión
    $_SESSION['usuarios'][$numero_pedido] = array(
        'nombre' => $_POST['nombre'],
        'numero_pedido' => $numero_pedido,
        'cedula' => $_POST['cedula'],
        'productos' => $productos_pedido,
        'imagen_cliente' => $nombre_imagen
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>El Cañagüatero - Pedidos</title>
</head>
<body>
    <h1>RESTAURANTE EL CAÑAGÜATERO</h1>
    <div style="border: 1px solid black; padding: 10px; margin-top: 20px;">
    <h2>Crear Pedido</h2>
    <form method="post" action="" enctype="multipart/form-data">
        <input type="text" name="nombre" placeholder="Nombre" required><br>
        <input type="text" name="cedula" placeholder="Cedula" required><br><br>
        <label for="tacos">Cantidad de Tacos ($3000 c/u):</label><br>
        <input type="number" id="tacos" name="tacos" value="0"><br><br>
        <label for="bebidas">Cantidad de Bebidas ($5000 c/u):</label><br>
        <input type="number" id="bebidas" name="bebidas" value="0"><br><br>
        <input type="file" name="imagen_cliente" accept="image/*" required><br><br>
        <button name="add">Realizar pedido</button>
    </form>
</div>

    <div style="border: 1px solid black; padding: 10px; margin-top: 20px;">
        <h2>Eliminar Pedido</h2>
        <form method="post" action="">
            <input type="text" name="key" placeholder="Número de Pedido a Eliminar">
            <button name="del">Eliminar</button>
        </form>
    </div>

    <!-- Módulo para listar todos los pedidos del mismo cliente -->
    <?php
    if (!empty($_SESSION['usuarios'])) {
        echo "<h2>Pedidos del mismo cliente</h2>";
        // Obtener la cédula del primer cliente para listar sus pedidos
        $cedula_cliente = reset($_SESSION['usuarios'])['cedula'];
        // Llamar a la función para listar los pedidos del cliente
        listar_pedidos_cliente($cedula_cliente);
    }
    ?>

    <!-- Módulo para listar todos los pedidos ordenados de mayor a menor por su valor final -->
    <?php
    if (!empty($_SESSION['usuarios'])) {
        echo "<h2>Pedidos ordenados por valor total</h2>";
        // Llamar a la función para listar los pedidos ordenados
        listar_pedidos_ordenados();
    }
    ?>
        
    <!-- Función para listar los pedidos del mismo cliente -->
    <?php
    function listar_pedidos_cliente($cedula) {
        $total_pedidos_cliente = 0; // Inicializar la cuenta total de pedidos del cliente
        foreach ($_SESSION['usuarios'] as $pedido) {
            if ($pedido['cedula'] === $cedula) {
                echo "<div>";
                echo "<p>Número de Pedido: #" . $pedido['numero_pedido'] . "</p>";
                echo "<p>Nombre del cliente: " . $pedido['nombre'] . "</p>";
                echo "<p>Cédula del Cliente: " . $pedido['cedula'] . "</p>";
                echo "<p>Productos:";
                foreach ($pedido['productos'] as $producto => $cantidad) {
                    echo " $cantidad $producto,";
                }
                echo "</p>";
                echo "<p>Valor Total: $" . calcular_valor_pedido($pedido) . "</p>";
                $total_pedidos_cliente += calcular_valor_pedido($pedido); // Sumar el valor total del pedido al total del cliente
                if ($pedido['imagen_cliente']) {
                    echo "<img src='uploads/" . $pedido['imagen_cliente'] . "' alt='Imagen del Cliente'>";
                } else {
                    echo "<hp>Imagen del Cliente: No disponible</hp><br><br>";
                }
                echo "</div>";
            }
        }
        // Mostrar la cuenta total de los pedidos del cliente
        echo "<h4>Total de Pedidos del Cliente: $" . $total_pedidos_cliente . "</h4>";
    }
    
    function listar_pedidos_ordenados() {
        if (!empty($_SESSION['usuarios'])) {
            // Crear una copia de los pedidos para ordenarlos
            $pedidos_ordenados = $_SESSION['usuarios'];
            // Ordenar los pedidos por su valor total en orden descendente
            usort($pedidos_ordenados, function($a, $b) {
                return calcular_valor_pedido($b) - calcular_valor_pedido($a);
            });
            
            foreach ($pedidos_ordenados as $pedido) {
                echo "<div>";
                echo "<p>Número de Pedido: #" . $pedido['numero_pedido'] . "</p>";
                echo "<p>Nombre del cliente: " . $pedido['nombre'] . "</p>";
                echo "<p>Cédula del Cliente: " . $pedido['cedula'] . "</p>";
                echo "<p>Productos:";
                foreach ($pedido['productos'] as $producto => $cantidad) {
                    echo " $cantidad $producto,";
                }
                echo "</p>";
                echo "<p>Valor Total: $" . calcular_valor_pedido($pedido) . "</p>";
                if ($pedido['imagen_cliente']) {
                    echo "<img src='uploads/" . $pedido['imagen_cliente'] . "' alt='Imagen del Cliente'>";
                } else {
                    echo "<p>Imagen del Cliente: No disponible</p><br>";
                }
                echo "</div>";
            }
        }
    }
    ?>
    