<?php 
class Loja5_ClearSale_Start_Admin {
	
	public function init(){
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'configuracoes' ) );
		if(get_option('loja5_woo_clearsale_start_config_ativado')==1){
			//add_action( 'add_meta_boxes', array( $this, 'analise_metabox' ) );
			add_action( 'woocommerce_admin_order_data_after_order_details', array($this, 'form_clearsale'));
		}
	}
	
	public function analise_metabox() {
		add_meta_box(
			'loja5_woo_clearsale_start',
			'Analise Clearsale Start',
			array( $this, 'metabox' ),
			'shop_order',
			'side',
			'default'
		);
	}
	
	public function form_clearsale( $order ){
		//dados do pedido
		$order_id = $order->get_id();
		$order_data = $order->get_data();
		
		//ambiente
		if(get_option('loja5_woo_clearsale_start_config_ambiente')=='h'){
			$url = "https://homolog.clearsale.com.br/start/Entrada/EnviarPedido.aspx";
		}else{
			$url = "https://www.clearsale.com.br/start/Entrada/EnviarPedido.aspx";
		}
		
		//configs
		$cod = get_option('loja5_woo_clearsale_start_config_id');
		$consulta = get_option('loja5_woo_clearsale_start_config_consulta');
		$bin = get_option('loja5_woo_clearsale_start_config_meta_bin');
		
		//trata o telefone
		$ddd = $tel = '';
		$tell_full = substr(preg_replace('/\D/', '', $order_data['billing']['phone']),-11);
		if(strlen($tell_full)==10){
			$ddd = substr($tell_full,0,2);
			$tel = substr($tell_full,-8);
		}else if(strlen($tell_full)==11){
			$ddd = substr($tell_full,0,2);
			$tel = substr($tell_full,-9);
		}
		
		//campos a consultar
		$campos = array(
			//dados pedidos
          	'CodigoIntegracao' => trim($cod),
			'IP' => urlencode(get_post_meta( $order_id, '_customer_ip_address', true )),
            'TipoPagamento' => 1,
            'PedidoID' => urlencode($order_data['id']),
            'Data' => urlencode($order_data['date_created']->date('d/m/Y H:i:s')),
	    //'Data' => urlencode($order_data['date_created']->date('Y-m-d H:i:s')),
            'Total' => urlencode(number_format($order_data['total'],2,'.','')),
			//dados cobranca
            'Cobranca_Nome' => urlencode($order_data['billing']['first_name'].' '.$order_data['billing']['last_name']),
            'Cobranca_Email' => urlencode($order_data['billing']['email']),
            'Cobranca_Documento' => urlencode(get_post_meta( $order_id, '_billing_cpf', true )),
            'Cobranca_DDD_Telefone_1' => urlencode($ddd),
            'Cobranca_Telefone_1' => urlencode($tel),
            'Entrega_Nome' => urlencode($order_data['shipping']['first_name'].' '.$order_data['shipping']['last_name']),
            'Entrega_Email' => urlencode($order_data['billing']['email']),
            'Entrega_Documento' => urlencode(get_post_meta( $order_id, '_billing_cpf', true )),
			//endereco de entrega
            'Entrega_Logradouro' => urlencode($order_data['shipping']['address_1']),
            'Entrega_Logradouro_Numero' => urlencode(get_post_meta( $order_id, '_shipping_number', true )),
            'Entrega_Logradouro_Complemento' => urlencode($order_data['shipping']['address_2']),
            'Entrega_Bairro' => urlencode(get_post_meta( $order_id, '_shipping_neighborhood', true )),
            'Entrega_Cidade' => urlencode($order_data['shipping']['city']),
            'Entrega_Estado' => urlencode($order_data['shipping']['state']),
            'Entrega_CEP' => urlencode($order_data['shipping']['postcode']),
            'Entrega_Pais' => urlencode($order_data['shipping']['country']),
            'Entrega_DDD_Telefone_1' => urlencode($ddd),
            'Entrega_Telefone_1' => urlencode($tel),
        );
		
		//produtos 
		$i=0; 
		foreach ($order->get_items() as $item_key => $item_values){ 
			$i++;
            $item_data = $item_values->get_data();
            $campos['Item_ID_'.$i] = urlencode($item_data['product_id']);
            $campos['Item_Nome_'.$i] = urlencode($item_data['name']);
            $campos['Item_Qtd_'.$i] = urlencode($item_data['quantity']);
            $campos['Item_Valor_'.$i] = urlencode(number_format($item_data['total'],2,',','.'));
        }
		
		//custom dados
		if(!empty($bin) && get_post_meta( $order_id, $bin, true )){
			$campos = array(
				'Cartao_Bin' => get_post_meta( $order_id, $bin, true )
			);
		}
		
		//url iframe vazio
		$url_iframe = plugins_url().'/loja5-woo-clearsale-start/iframe.php';
		
		//realiza a consulta 
		$disparo_automatico = false;
		if($consulta=='a'){
			//faz a consulta automatica
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_POST, true);
			curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($campos));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
			$result = curl_exec($ch);
			curl_close($ch);
			//salva a consulta em meta 
			if(strpos($result, 'ERRO INTEGRAÇÃO') !== false){
				//dispara a consulta por erro
				$disparo_automatico = true;
			}else{
				//salva meta com sucesso
				update_post_meta( $order_id, '_url_clearsale_start', ''.$url.'?codigointegracao='.trim($cod).'&PedidoID='.$order_id );
				//bloqueia o disparo por sucesso 
				$disparo_automatico = false;
			}
		}
		
		//se url ja consultada com sucesso 
		if(!empty(get_post_meta( $order_id, '_url_clearsale_start', true ))){
			//consulta ja salva bloqueia o disparo
			$disparo_automatico = false;
			//url da consulta feita
			$url_iframe = get_post_meta( $order_id, '_url_clearsale_start', true );
		}
		?>
		<script>
		function dados_enviados(){
			var dados_enviados = '';
			<?php foreach($campos as $k=>$v){?>
				dados_enviados += '<?php echo $k;?> => <?php echo $v;?>\n';
			<?php } ?>
			console.log(dados_enviados);
			alert(dados_enviados);
		}
		function realizar_consulta_clearsale(){
			jQuery('#iframe_clearsale_start').show();
			var f = document.createElement("form");
			f.setAttribute('method',"post");
			f.setAttribute('id',"form_clearsale");
			f.setAttribute('target',"iframe_clearsale_start");
			f.setAttribute('action',"<?php echo $url;?>");
			<?php foreach($campos as $k=>$v){?>
			var i = document.createElement("input");
			i.setAttribute('type',"hidden");
			i.setAttribute('name',"<?php echo $k;?>");
			i.setAttribute('value',"<?php echo $v;?>");
			f.appendChild(i);
			<?php } ?>
			document.getElementsByTagName('body')[0].appendChild(f);
			document.getElementById("form_clearsale").submit();
		}
		<?php if($disparo_automatico){?>
			setTimeout(function(){ realizar_consulta_clearsale(); }, 500);
		<?php } ?>
		</script>
		<div class="loja5-woo-clearsale-start">
		<fieldset>
		<iframe style="position: relative; height: 100px;<?php if($consulta=='m'){?>display:none;<?php } ?>" name="iframe_clearsale_start" id="iframe_clearsale_start" src="<?php echo $url_iframe;?>"></iframe>
		<br>
		<button type="button" onclick="realizar_consulta_clearsale()" style="width: 100%;" class="button button-primary">Consultar Clearsale <?php echo (get_option('loja5_woo_clearsale_start_config_ambiente')=='h')?'(homologa&ccedil;&atilde;o)':'';?></button>
		<p><i><a onclick="dados_enviados()">Clique aqui</a> para visualizar os dados enviados a ClearSale.</i></p>
		</fieldset>
		</div>
		<?php
	}
	
	public function menu() {
        add_submenu_page(
            'woocommerce',
            'Clearsale Start [Loja5]',
			'Clearsale Start [Loja5]',
            'manage_woocommerce',
            'loja5-woo-clearsale-start',
            array( $this, 'admin' )
        );
    }
	
	public function configuracoes() {
		register_setting( 'loja5_woo_clearsale_start_group', 'loja5_woo_clearsale_start_config_ativado' );
		register_setting( 'loja5_woo_clearsale_start_group', 'loja5_woo_clearsale_start_config_id' );
		register_setting( 'loja5_woo_clearsale_start_group', 'loja5_woo_clearsale_start_config_ambiente' );
		register_setting( 'loja5_woo_clearsale_start_group', 'loja5_woo_clearsale_start_config_consulta' );
		register_setting( 'loja5_woo_clearsale_start_group', 'loja5_woo_clearsale_start_config_meta_bin' );
	}
	
	public function admin() {
		?>
        <div class="wrap">
            <h1><?php echo __( 'Configura&ccedil;&otilde;es', 'loja5-woo-clearsale-start-clearsale-start' ); ?></h1>
            <form method="post" action="options.php">
			
			<?php settings_fields( 'loja5_woo_clearsale_start_group' ); ?>
			<?php do_settings_sections( 'loja5-woo-clearsale-start-rastreamento-configuracoes' ); ?>

			
			<table class="form-table">
			
			<tr valign="top">
			<th scope="row"><?php echo __( 'Ativo:', 'loja5-woo-clearsale-start-clearsale-start' ); ?></th>
			<td><input type="checkbox" name="loja5_woo_clearsale_start_config_ativado" value="1" <?php echo (get_option('loja5_woo_clearsale_start_config_ativado')==1)?'checked':'';?> /> <p class="description">Marque para ativar o plugin na loja, &eacute; obrigatorio que a loja possua o plugin <a href="https://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/" target="_blank">Brazilian Market on WooCommerce</a> instalado.</p></td>
			</tr>

			<tr valign="top">
			<th scope="row"><?php echo __( 'Código de Integração:', 'loja5-woo-clearsale-start-clearsale-start' ); ?></th>
			<td><input type="text" name="loja5_woo_clearsale_start_config_id" value="<?php echo esc_attr( get_option('loja5_woo_clearsale_start_config_id') ); ?>" /> <p class="description">C&oacute;digo de integra&ccedil;&atilde;o fornecido por a Clersale (<a href="https://br.clear.sale/antifraude/ecommerce/start" target="_blank">Integra&ccedil;&atilde;o Start</a>).</p></td>
			</tr>
			
			<tr valign="top">
			<th scope="row"><?php echo __( 'Ambiente:', 'loja5-woo-clearsale-start-clearsale-start' ); ?></th>
			<td>
			<select name="loja5_woo_clearsale_start_config_ambiente">
			<option value="h"<?php echo (get_option('loja5_woo_clearsale_start_config_ambiente')=='h')?' selected':'';?>>Homologa&ccedil;&atilde;o</option>
			<option value="p"<?php echo (get_option('loja5_woo_clearsale_start_config_ambiente')=='p')?' selected':'';?>>Produ&ccedil;&atilde;o</option>
			</select> <p class="description">Ambiente de integra&ccedil;&atilde;o Clersale Start.</p>
			</td>
			</tr>
			
			<tr valign="top">
			<th scope="row"><?php echo __( 'Tipo de Consulta:', 'loja5-woo-clearsale-start-clearsale-start' ); ?></th>
			<td>
			<select name="loja5_woo_clearsale_start_config_consulta">
			<option value="a"<?php echo (get_option('loja5_woo_clearsale_start_config_consulta')=='a')?' selected':'';?>>Automatica</option>
			<option value="m"<?php echo (get_option('loja5_woo_clearsale_start_config_consulta')=='m')?' selected':'';?>>Manual</option>
			</select> <p class="description">Como deseja que seja a consulta de risco nos detalhes do pedido.</p>
			</td>
			</tr>
			
			<tr valign="top">
			<th scope="row"><?php echo __( 'Meta BIN do Cartão:', 'loja5-woo-clearsale-start-clearsale-start' ); ?></th>
			<td><input type="text" name="loja5_woo_clearsale_start_config_meta_bin" value="<?php echo esc_attr( get_option('loja5_woo_clearsale_start_config_meta_bin') ); ?>" /> <p class="description"><?php echo __( 'Meta contendo o BIN do cartão [XXXX********XXXX] usado no pagamento, este meta deve ser salvo diretamente por o plugin de pagamento usado na loja. Ex: _bin_cartao (opcional)', 'loja5-woo-clearsale-start-clearsale-start' ); ?></p></td>
			</tr>
			
			</table>
			
            <?php
            submit_button();
            ?>
            </form>
        </div>
        <?php
	}

}
?>
