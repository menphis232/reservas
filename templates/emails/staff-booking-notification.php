<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nueva cita asignada</title>
</head>
<body>
    <h2>Hola <?php echo $data['staff_name']; ?>,</h2>
    
    <p>Se te ha asignado una nueva cita con los siguientes detalles:</p>
    
    <ul>
        <li><strong>Servicio:</strong> <?php echo $data['booking']->service_name; ?></li>
        <li><strong>Ubicación:</strong> <?php echo $data['booking']->location_name; ?></li>
        <li><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($data['booking']->start_date)); ?></li>
        <li><strong>Hora:</strong> <?php echo date('H:i', strtotime($data['booking']->start_date)); ?></li>
    </ul>

    <p>Por favor, accede al panel de administración para ver más detalles.</p>
</body>
</html> 