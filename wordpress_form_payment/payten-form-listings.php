<?php
ob_start();
function wp_payten_form_listings_page() {
?>

<div>
	<h1>Pay10 Payment Details</h1>
		<table cellpadding="0" cellspacing="0" bgcolor="#ccc" width="99%">
			<tr>
				<td><table cellpadding="10" cellspacing="1" width="100%">
				<?php
					global $wpdb;
					
					$total = $wpdb->get_var("SELECT COUNT(id)  FROM " . $wpdb->prefix . "payten_form");
					
					$records_per_page = 10;
					$page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
					$offset = ( $page * $records_per_page ) - $records_per_page;
					
					$formEntries = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "payten_form order by date desc limit ".$offset. " , ".$records_per_page);
					
					if (count($formEntries) > 0) { ?>
					<thead>
						<tr>
							<th width="8%" align="left" bgcolor="#FFFFFF">Id</th>
							<th width="8%" align="left" bgcolor="#FFFFFF">Name</th>
							<th width="10%" align="left" bgcolor="#FFFFFF">Email</th>
							<th width="8%" align="left" bgcolor="#FFFFFF">Phone</th>
							<th width="10%" align="left" bgcolor="#FFFFFF">Address</th>
							<th width="8%" align="left" bgcolor="#FFFFFF">City</th>
							<th width="8%" align="left" bgcolor="#FFFFFF">State</th>
							<th width="8%" align="left" bgcolor="#FFFFFF">Order Id</th>
							<th width="8%" align="left" bgcolor="#FFFFFF">Country</th>
							<th width="8%" align="left" bgcolor="#FFFFFF">Zipcode</th>
							<th width="8%" align="left" bgcolor="#FFFFFF">Amount</th>
							<th width="8%" align="left" bgcolor="#FFFFFF">Payment Status</th>
							<th width="8%" align="left" bgcolor="#FFFFFF">Date</th>
							<th width="8%" align="left" bgcolor="#FFFFFF">Update</th>
						</tr>
						<?php foreach ($formEntries as $row) { ?>
						<tr>
							<td bgcolor="#FFFFFF"><?php echo $row->id ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->name ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->email; ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->phone; ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->address; ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->city; ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->state; ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->order_id; ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->country; ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->zip; ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->amount; ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->payment_status; ?></td>
							<td bgcolor="#FFFFFF"><?php echo $row->date; ?></td>
							<td bgcolor="#FFFFFF">
								<?php $form_url = admin_url('admin-post.php'); $data = (($row->payment_status=='Pending') || ($row->payment_status=='Sent to Bank')? $form_url:'#');?>
                                  <form action="<?php echo $data; ?>" method="post">
								<button <?php  $data = (($row->payment_status=='Pending') || ($row->payment_status=='Sent to Bank')? 'null':'disabled');echo $data; ?>> <?php   $data =(($row->payment_status=='Pending') || ($row->payment_status=='Sent to Bank')? 'Update':'Not Required');echo $data; ?></button>
								    <input type="hidden" name="order_id" value="<?php echo $row->order_id; ?>">		
								    <input type="hidden" name="action" value="my_media_update">
								</form>
							</td>
						</tr>
						<?php } ?>
					</thead>
					<?php } else { echo "No Record's Found."; } ?>
				</table></td>
			</tr>
		</table>		
		<?php
		$pagination = paginate_links( array(
				'base' => add_query_arg( 'cpage', '%#%' ),
				'format' => '',
				'prev_text' => __('Previous'),
				'next_text' => __('Next'),
				'total' => ceil($total / $records_per_page),
				'current' => $page
		));
		?>		
		<div class="form-pagination">
			<?php echo $pagination; ?>
		</div>
	</div>
<?php } ?>

<?php
//  function kh_update_media_seo() {
//      $to_email = "rohit.singh@pay10.com";
// $subject = "Test email to send from XAMPP";
// $body = "Hi, This is test mail to check how to send mail from Localhost Using Gmail ";
// $headers = "From: dwipendra.singh@pay10.com";
// mail($to_email, $subject, $body, $headers);

// }
// add_action( 'admin_post_my_media_update', 'kh_update_media_seo' );
?>
