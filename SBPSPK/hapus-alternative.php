<?php require_once('includes/init.php'); ?>
<?php cek_login($role = array(1, 2)); ?>

<?php
$ada_error = false;
$result = '';

$id_alternative = (isset($_GET['id'])) ? trim($_GET['id']) : '';

if(!$id_alternative) {
	$ada_error = 'Maaf, data tidak dapat diproses.';
} else {
	$query = $pdo->prepare('SELECT id_alternative FROM alternative WHERE id_alternative = :id_alternative');
	$query->execute(array('id_alternative' => $id_alternative));
	$result = $query->fetch();
	
	if(empty($result)) {
		$ada_error = 'Maaf, data tidak dapat diproses.';
	} else {
		
		$handle = $pdo->prepare('DELETE FROM nilai_alternative WHERE id_alternative = :id_alternative');				
		$handle->execute(array(
			'id_alternative' => $result['id_alternative']
		));
		$handle = $pdo->prepare('DELETE FROM alternative WHERE id_alternative = :id_alternative');				
		$handle->execute(array(
			'id_alternative' => $result['id_alternative']
		));
		redirect_to('list-alternative.php?status=sukses-hapus');
		
	}
}
?>

<?php
$judul_page = 'Hapus Alternative';
require_once('template-parts/header.php');
?>

	<div class="main-content-row">
	<div class="container clearfix">
	
		<?php include_once('template-parts/sidebar-alternative.php'); ?>
	
		<div class="main-content the-content">
			<h1><?php echo $judul_page; ?></h1>
			
			<?php if($ada_error): ?>
			
				<?php echo '<p>'.$ada_error.'</p>'; ?>	
			
			<?php endif; ?>
			
		</div>
	
	</div><!-- .container -->
	</div><!-- .main-content-row -->


<?php
require_once('template-parts/footer.php');