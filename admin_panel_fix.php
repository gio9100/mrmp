<?php
// Script to fix the delete piece functionality
// This code should replace lines 314-324 in admin_panel.php

    // NUEVO: Eliminar imagen de galería
    if(isset($_GET['eliminar_img_gal'])){
        $id_img = intval($_GET['eliminar_img_gal']);
        $res_img = $conexion->query("SELECT imagen FROM piezas_imagenes WHERE id=$id_img");
        if($row_img = $res_img->fetch_assoc()){
            if(file_exists("uploads/".$row_img['imagen'])){
                unlink("uploads/".$row_img['imagen']);
            }
        }
        $conexion->query("DELETE FROM piezas_imagenes WHERE id=$id_img");
        $_SESSION['mensaje'] = "✅ Imagen de galería eliminada.";
        header("Location: admin_panel.php");
        exit;
    }

 // 137: Comentario: Eliminar pieza
 // Eliminar pieza 
    // 138: Si piden eliminar pieza...
    if(isset($_GET['eliminar_pieza'])){
        // 139: ID a entero
        $id = intval($_GET['eliminar_pieza']);
        
        // NUEVO: Primero eliminamos las referencias en detalle_pedidos
        $conexion->query("DELETE FROM detalle_pedidos WHERE pieza_id=$id");
        
        // NUEVO: Eliminamos las imágenes de la galería
        $res_imgs = $conexion->query("SELECT imagen FROM piezas_imagenes WHERE pieza_id=$id");
        while($img_row = $res_imgs->fetch_assoc()){
            if(file_exists("uploads/".$img_row['imagen'])){
                unlink("uploads/".$img_row['imagen']);
            }
        }
        $conexion->query("DELETE FROM piezas_imagenes WHERE pieza_id=$id");
        
        // NUEVO: Eliminamos la imagen principal si existe
        $res_pieza = $conexion->query("SELECT imagen FROM piezas WHERE id=$id");
        if($pieza_data = $res_pieza->fetch_assoc()){
            if(!empty($pieza_data['imagen']) && file_exists("uploads/".$pieza_data['imagen'])){
                unlink("uploads/".$pieza_data['imagen']);
            }
        }
        
        // 140: Borramos la pieza
        $conexion->query("DELETE FROM piezas WHERE id=$id");
        // 141: Mensaje
        $_SESSION['mensaje'] = "✅ Pieza eliminada correctamente.";
        // 142: Recargamos
        header("Location: admin_panel.php");
        // 143: Bye
        exit;
    // 144: Cerramos if
    }

    // 145: Comentario: Actualizar pieza existente
    // Actualizar pieza
    // 146: Si mandan actualizar pieza...
    if(isset($_POST['actualizar_pieza'])){
