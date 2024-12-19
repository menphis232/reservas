<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="employee-selector">
    <form method="get">
        <input type="hidden" name="page" value="menphis-employee-dashboard">
        <select name="employee_id" onchange="this.form.submit()">
            <option value="0"><?php _e('Todos los empleados', 'menphis-reserva'); ?></option>
            <?php foreach ($employees as $employee): ?>
                <option value="<?php echo esc_attr($employee->ID); ?>" 
                        <?php selected($this->employee_id, $employee->ID); ?>>
                    <?php echo esc_html($employee->display_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div> 