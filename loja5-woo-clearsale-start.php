<?php
/*
  Plugin Name: Clearsale Start - Loja5
  Description: Analise de risco para pedidos Woocommerce via Clearsale Start.
  Version: 1.0
  Author: Loja5.com.br
  Author URI: http://www.loja5.com.br
  Copyright: © 2009-2020 Loja5.com.br.
  License: Free / GPL
*/

if ( ! class_exists( ' WC_Loja5_Clearsale_Start' ) ) {
    
	class WC_Loja5_Clearsale_Start {
		
		protected static $instance = null;
		
		private function __construct() {
			//requerido
			if( !function_exists('is_plugin_active') ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			//init admin
			if( is_admin() && is_plugin_active('woocommerce/woocommerce.php') ) {
				//aplica filtros e tela de configuracao
				add_filter('plugin_action_links_'.plugin_basename( __FILE__ ), array( $this, 'link_configuracao' ));
				include_once(dirname(__FILE__).'/classes/admin.php');
				$admin = new Loja5_ClearSale_Start_Admin();
				$admin->init();
			}elseif( is_admin() && !is_plugin_active('woocommerce/woocommerce.php') ){
				//alerta se woo não existir
				add_action( 'admin_notices', array( $this, 'alerta_versao' ) );
			}
		}
		
		function link_configuracao( $links ) {
			//link de acesso rapido a config
			$plugin_links = array();
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
				$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=loja5-woo-clearsale-start' ) ) . '">' . __( 'Configurar', 'loja5-woo-link-clearsale-start' ) . '</a>';
			} else {
				$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=loja5-woo-clearsale-start' ) ) . '">' . __( 'Configurar', 'loja5-woo-link-clearsale-start' ) . '</a>';
			}
			return array_merge( $plugin_links, $links );
		}
		
		public function alerta_versao(){
			//alerta versao
			echo '<div class="error">';
			echo '<p><strong>Ops:</strong> O plugin Clearsale Start [Loja5] requer que exista o Woocommerce instalado e ativado para poder funcionar!</p>';
			echo '</div>';
		}
		
		public static function get_instance() {
			//init
			if ( null === self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}
	}

	//inicializa o plugin no wordpress
	add_action( 'plugins_loaded', array( 'WC_Loja5_Clearsale_Start', 'get_instance' ) );

}
?>
