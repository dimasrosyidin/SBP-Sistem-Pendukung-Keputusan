<?php

require_once('includes/init.php');

/* ---------------------------------------------
 * Load Header
 * ------------------------------------------- */
$judul_page = 'Perankingan Menggunakan Metode SAW';
require_once('template-parts/header.php');

/* ---------------------------------------------
 * Set jumlah digit di belakang koma
 * ------------------------------------------- */
$digit = 4;

/* ---------------------------------------------
 * Fetch semua kriteria
 * ------------------------------------------- */
$query = $pdo->prepare('SELECT id_kriteria, nama, type, bobot
	FROM kriteria ORDER BY urutan_order ASC');
$query->execute();
$query->setFetchMode(PDO::FETCH_ASSOC);
$kriterias = $query->fetchAll();

/* ---------------------------------------------
 * Fetch semua alternative (alternatif)
 * ------------------------------------------- */
$query2 = $pdo->prepare('SELECT id_alternative, no_alternative FROM alternative');
$query2->execute();			
$query2->setFetchMode(PDO::FETCH_ASSOC);
$alternatives = $query2->fetchAll();


/* >>> STEP 1 ===================================
 * Matrix Keputusan (X)
 * ------------------------------------------- */
$matriks_x = array();
$list_kriteria = array();
foreach($kriterias as $kriteria):
	$list_kriteria[$kriteria['id_kriteria']] = $kriteria;
	foreach($alternatives as $alternative):
		
		$id_alternative = $alternative['id_alternative'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		// Fetch nilai dari db
		$query3 = $pdo->prepare('SELECT nilai FROM nilai_alternative
			WHERE id_alternative = :id_alternative AND id_kriteria = :id_kriteria');
		$query3->execute(array(
			'id_alternative' => $id_alternative,
			'id_kriteria' => $id_kriteria,
		));			
		$query3->setFetchMode(PDO::FETCH_ASSOC);
		if($nilai_alternative = $query3->fetch()) {
			// Jika ada nilai kriterianya
			$matriks_x[$id_kriteria][$id_alternative] = $nilai_alternative['nilai'];
		} else {			
			$matriks_x[$id_kriteria][$id_alternative] = 0;
		}

	endforeach;
endforeach;

/* >>> STEP 3 ===================================
 * Matriks Ternormalisasi (R)
 * ------------------------------------------- */
$matriks_r = array();
foreach($matriks_x as $id_kriteria => $nilai_alternatives):
	
	$tipe = $list_kriteria[$id_kriteria]['type'];
	foreach($nilai_alternatives as $id_alternatif => $nilai) {
		if($tipe == 'benefit') {
			$nilai_normal = $nilai / max($nilai_alternatives);
		} elseif($tipe == 'cost') {
			$nilai_normal = min($nilai_alternatives) / $nilai;
		}
		
		$matriks_r[$id_kriteria][$id_alternatif] = $nilai_normal;
	}
	
endforeach;


/* >>> STEP 4 ================================
 * Perangkingan
 * ------------------------------------------- */
$ranks = array();
foreach($alternatives as $alternative):

	$total_nilai = 0;
	foreach($list_kriteria as $kriteria) {
	
		$bobot = $kriteria['bobot'];
		$id_alternative = $alternative['id_alternative'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		$nilai_r = $matriks_r[$id_kriteria][$id_alternative];
		$total_nilai = $total_nilai + ($bobot * $nilai_r);

	}
	
	$ranks[$alternative['id_alternative']]['id_alternative'] = $alternative['id_alternative'];
	$ranks[$alternative['id_alternative']]['no_alternative'] = $alternative['no_alternative'];
	$ranks[$alternative['id_alternative']]['nilai'] = $total_nilai;
	
endforeach;
 
?>

<div class="main-content-row">
<div class="container clearfix">	

	<div class="main-content main-content-full the-content">
		
		<h1><?php echo $judul_page; ?></h1>
		
		<!-- STEP 1. Matriks Keputusan(X) ==================== -->		
		<h3>Step 1: Matriks Keputusan (X)</h3>
		<table class="pure-table pure-table-striped">
			<thead>
				<tr class="super-top">
					<th rowspan="2" class="super-top-left">No. alternative</th>
					<th colspan="<?php echo count($kriterias); ?>">Kriteria</th>
				</tr>
				<tr>
					<?php foreach($kriterias as $kriteria ): ?>
						<th><?php echo $kriteria['nama']; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach($alternatives as $alternative): ?>
					<tr>
						<td><?php echo $alternative['no_alternative']; ?></td>
						<?php						
						foreach($kriterias as $kriteria):
							$id_alternative = $alternative['id_alternative'];
							$id_kriteria = $kriteria['id_kriteria'];
							echo '<td>';
							echo $matriks_x[$id_kriteria][$id_alternative];
							echo '</td>';
						endforeach;
						?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<!-- STEP 2. Bobot Preferensi (W) ==================== -->
		<h3>Step 2: Bobot Preferensi (W)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>
				<tr>
					<th>Nama Kriteria</th>
					<th>Type</th>
					<th>Bobot (W)</th>						
				</tr>
			</thead>
			<tbody>
				<?php foreach($kriterias as $hasil): ?>
					<tr>
						<td><?php echo $hasil['nama']; ?></td>
						<td>
						<?php
						if($hasil['type'] == 'benefit') {
							echo 'Benefit';
						} elseif($hasil['type'] == 'cost') {
							echo 'Cost';
						}							
						?>
						</td>
						<td><?php echo $hasil['bobot']; ?></td>							
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<!-- Step 3: Matriks Ternormalisasi (R) ==================== -->
		<h3>Step 3: Matriks Ternormalisasi (R)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>
				<tr class="super-top">
					<th rowspan="2" class="super-top-left">No. alternative</th>
					<th colspan="<?php echo count($kriterias); ?>">Kriteria</th>
				</tr>
				<tr>
					<?php foreach($kriterias as $kriteria ): ?>
						<th><?php echo $kriteria['nama']; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach($alternatives as $alternative): ?>
					<tr>
						<td><?php echo $alternative['no_alternative']; ?></td>
						<?php						
						foreach($kriterias as $kriteria):
							$id_alternative = $alternative['id_alternative'];
							$id_kriteria = $kriteria['id_kriteria'];
							echo '<td>';
							echo round($matriks_r[$id_kriteria][$id_alternative], $digit);
							echo '</td>';
						endforeach;
						?>
					</tr>
				<?php endforeach; ?>				
			</tbody>
		</table>		
		
		
		<!-- Step 4: Perangkingan ==================== -->
		<?php		
		$sorted_ranks = $ranks;		
		// Sorting
		if(function_exists('array_multisort')):
			$no_alternative = array();
			$nilai = array();
			foreach ($sorted_ranks as $key => $row) {
				$no_alternative[$key]  = $row['no_alternative'];
				$nilai[$key] = $row['nilai'];
			}
			array_multisort($nilai, SORT_DESC, $no_alternative, SORT_ASC, $sorted_ranks);
		endif;
		?>		
		<h3>Step 4: Perangkingan (V)</h3>			
		<table class="pure-table pure-table-striped">
			<thead>					
				<tr>
					<th class="super-top-left">No. alternative</th>
					<th>Ranking</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($sorted_ranks as $alternative ): ?>
					<tr>
						<td><?php echo $alternative['no_alternative']; ?></td>
						<td><?php echo round($alternative['nilai'], $digit); ?></td>											
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>			
		
	</div>

</div><!-- .container -->
</div><!-- .main-content-row -->

<?php
require_once('template-parts/footer.php');