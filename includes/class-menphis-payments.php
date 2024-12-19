<?php
class MenphisPayments {
    private $gateways = array();

    public function __construct() {
        add_action('init', array($this, 'init_payment_gateways'));
        add_action('wp_ajax_menphis_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_menphis_process_payment', array($this, 'process_payment'));
    }

    public function init_payment_gateways() {
        // Inicializar pasarelas de pago
        $this->gateways['stripe'] = new MenphisStripeGateway();
        $this->gateways['paypal'] = new MenphisPayPalGateway();
    }

    public function process_payment() {
        // Procesar pago según la pasarela seleccionada
    }
} 