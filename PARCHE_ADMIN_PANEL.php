<?php
/**
 * PARCHE PARA ADMIN_PANEL.PHP - LÍNEAS 386-401
 * 
 * INSTRUCCIONES:
 * 1. Abre admin_panel.php
 * 2. Ve a la línea 386
 * 3. REEMPLAZA las líneas 386-401 con el código de abajo
 */

    // 169: Comentario: Eliminar pieza con cascada para evitar errores de foreign key
    // Eliminar pieza
    // 170: Si piden eliminar...
    if(isset($_GET['eliminar_pieza'])){
        // 171: ID a entero
        $id = intval($_GET['eliminar_pieza']);
        
        // NUEVO: Primero eliminamos las referencias en detalle_pedidos para evitar foreign key error
        $conexion->query("DELETE FROM detalle_pedidos WHERE pieza_id=$id");
        
        // NUEVO: Eliminamos las imágenes de la galería (archivos físicos)
        $res_imgs = $conexion->query("SELECT imagen FROM piezas_imagenes WHERE pieza_id=$id");
        while($img_row = $res_imgs->fetch_assoc()){
            if(file_exists("uploads/".$img_row['imagen'])){
                unlink("uploads/".$img_row['imagen']);
            }
        }
        // Eliminamos los registros de la galería
        $conexion->query("DELETE FROM piezas_imagenes WHERE pieza_id=$id");
        
        // NUEVO: Eliminamos la imagen principal si existe (archivo físico)
        $res_pieza = $conexion->query("SELECT imagen FROM piezas WHERE id=$id");
        if($pieza_data = $res_pieza->fetch_assoc()){
            if(!empty($pieza_data['imagen']) && file_exists("uploads/".$pieza_data['imagen'])){
                unlink("uploads/".$pieza_data['imagen']);
            }
        }
        
        // 172: Finalmente borramos la pieza
        $conexion->query("DELETE FROM piezas WHERE id=$id");
        // 173: Mensaje
        $_SESSION['mensaje'] = "✅ Pieza eliminada correctamente.";
        // 174: Recargamos
        header("Location: admin_panel.php");
        // 175: Bye
        exit;
    // 176: Cerramos if
    }
