<?php
	if(!class_exists('osc_products')) :
	require_once(OSCOMMERCECLASSPATH .'/osc_currencies.class.php');

	class osc_products // DISPLAY OSC PRODUCTS
	{
		var $records_per_page;
		var $record_count;

		function osc_products()
		{
			$this->records_per_page = 5;
		}
		
		function osc_count_products($db, $cat_id = 0)
		{
			$sql = 'SELECT
						COUNT(p.products_id) AS cnt
					FROM
						products p
						INNER JOIN products_to_categories pc ON p.products_id = pc.products_id';

			if(!empty($cat_id))
				$sql.= ' WHERE pc.categories_id = '. $cat_id;

			$sql.= ' AND p.products_quantity > 0';

			$this->record_count = $db->get_var($sql);
  		}
	
		function osc_list_products($db, $shop_id, $cat_id)
		{
			$sql = 'SELECT
						vchUrl,
						vchUsername,
						vchPassword,
						vchDbName,
						vchHost
					FROM
						wp_oscommerce
					WHERE
						intShopId = '. $shop_id;

			$res_arr  = $db->get_results($sql);

			$shop_url = $res_arr[0]->vchUrl;

			$shop_db  = new wpdb($res_arr[0]->vchUsername, $res_arr[0]->vchPassword, $res_arr[0]->vchDbName, $res_arr[0]->vchHost);

			// GET TOTAL AMOUNT OF PRODUCTS
			//$this->osc_count_products($shop_db, $cat_id);
			
			$sql = 'SELECT
						p.products_id,
						p.products_image,
						pd.products_name,
						p.products_model,
						p.products_weight,
						p.products_quantity,
						p.products_price,
						p.manufacturers_id,
						m.manufacturers_name,
						p.products_tax_class_id,
						IF(s.status, s.specials_new_products_price, NULL) AS specials_new_products_price,
						IF(s.status, s.specials_new_products_price, p.products_price) AS final_price,
						pd.products_description
					FROM
						products_description pd
						INNER JOIN products p ON pd.products_id = p.products_id
						INNER JOIN products_to_categories pc ON p.products_id = pc.products_id
						LEFT JOIN manufacturers m ON p.manufacturers_id = m.manufacturers_id
						LEFT JOIN specials s ON p.products_id = s.products_id
					WHERE
						p.products_status = 1
						AND pd.language_id = 1
						AND pc.categories_id = '. $cat_id .'
						AND p.products_quantity > 0
					ORDER BY
						m.manufacturers_name,
						pd.products_name';
			
			/* CALCULATE PAGING
			if($this->record_count > 0)
			{	
				$max_page = ceil($this->record_count/$this->records_per_page);
				
				if($_GET['paged'] > $max_page) $paged = $max_page;
				
				$firstRecord = $this->records_per_page * ($_GET['paged'] - 1);
				
				if(!empty($_GET['paged']))
					$sql = sprintf('%s LIMIT %d, %d', $sql, $firstRecord, $this->records_per_page);
			}
			*/

			$res_prods = $shop_db->get_results($sql);
			
			if($shop_db->num_rows > 0)
			{
				$osCsid = md5('product_session');

				/* DISPLAY PAGING LINKS
				if($this->record_count > $this->records_per_page)
					$page_links = paginate_links(array('base'    => add_query_arg(array('paged' => '%#%')),
													   'format'  => '',
													   'total'   => $max_page,
													   'current' => $_GET['paged']));
				
				if($page_links)
					echo '<div class="tablenav-pages">'. $page_links .'</div>';
				*/

				$currencies = new osc_currencies($shop_db);

				for($j = 0 ; $j < count($res_prods) ; $j++)
				{
					echo '<div class="row">
							<div class="product-thumb">';
							
					if(!empty($res_prods[$j]->products_image))
						echo '<img src="'. $shop_url .'images/'. $res_prods[$j]->products_image .'" alt="'. $res_prods[$j]->products_name .'" title="'. $res_prods[$j]->products_name .'" style="border:none;" onClick="javascript: window.open(\''. $shop_url .'images/'. $res_prods[$j]->products_image .'\', \'\', \'menubar=no, resizable=yes, status=no, toolbar=no, scrollbars=yes, top=0, left=0, width=640, height=500\');">';
					else
						echo '<img src="'. OSCOMMERCEIMAGESURL .'/no_image.gif" alt="No Image" title="No Image" style="border:none;">';
					
					echo '</div>
							<div id="product-text">
								<span class="title">'. $res_prods[$j]->products_name .'</span>
								<p>';
								
					if(!empty($res_prods[$j]->products_model))
						echo 'Model: '. $res_prods[$j]->products_model .'<br>';

					if(!empty($res_prods[$j]->products_weight))
						echo 'Weight: '. $res_prods[$j]->products_weight .'<br>';

					echo 'No. Available: '. $res_prods[$j]->products_quantity .'<br>';

					if(!empty($res_prods[$j]->specials_new_products_price))
						echo 'Was: <s>'. $currencies->display_price($res_prods[$j]->products_price, $currencies->get_tax_rate($res_prods[$j]->products_tax_class_id)) .'</s> Now: <span class="product-special">'. $currencies->display_price($res_prods[$j]->specials_new_products_price, $currencies->get_tax_rate($res_prods[$j]->products_tax_class_id)) .'</span><br>';
					else
						echo 'Price: '. $currencies->display_price($res_prods[$j]->products_price, $currencies->get_tax_rate($res_prods[$j]->products_tax_class_id)) .'<br>';
					
					if(!empty($res_prods[$j]->products_description))
						echo '<br>'. $res_prods[$j]->products_description;

							echo '</p>
								<div class="bottom"><a href="javascript:void(0);" onClick="javascript: window.open(\''. wp_nonce_url($shop_url. 'checkout_shipping.php/cPath/'. $cat_id .'/sort/2a/action/buy_now/products_id/'. $res_prods[$j]->products_id .'?osCsid='. $osCsid) .'\', \'cart\', \'menubar=no, resizable=yes, status=no, toolbar=no, scrollbars=yes, top=0, left=0, width=640, height=500\');" title="Buy Now"><img src="'. OSCOMMERCEIMAGESURL .'/button_buy_now.gif" alt="Buy Now" title="Buy Now" style="border:none;"></a></div>
							</div>
						</div>
						<div style="clear:both; padding-top:5px; margin-bottom:20px; border-bottom:1px solid #EDEDED;"></div>';
				}
				
				unset($currencies);
				
				/* DISPLAY PAGING LINKS
				if($page_links)
					echo '<div class="tablenav-pages">'. $page_links .'</div>';
				*/
			}
			
			unset($shop_db);
  		}
	}
	endif;
?>