<?php

require_once('includes/init.php');

/* ---------------------------------------------
 * Load Header
 * ------------------------------------------- */
$judul_page = 'Perankingan Menggunakan Metode Hybrid SAW dan TOPSIS';
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

/* >>> STEP 4 ===================================
 * Matriks Y
 * ------------------------------------------- */
$matriks_y = array();
foreach($kriterias as $kriteria):
	foreach($alternatives as $alternative):
		
		$bobot = $kriteria['bobot'];
		$id_alternative = $alternative['id_alternative'];
		$id_kriteria = $kriteria['id_kriteria'];
		
		$nilai_r = $matriks_r[$id_kriteria][$id_alternative];
		$matriks_y[$id_kriteria][$id_alternative] = $bobot * $nilai_r;

	endforeach;
endforeach;


/* >>> STEP 5 ================================
 * Solusi Ideal Positif & Negarif
 * ------------------------------------------- */
$solusi_ideal_positif = array();
$solusi_ideal_negatif = array();
foreach($kriterias as $kriteria):

	$id_kriteria = $kriteria['id_kriteria'];
	$type_kriteria = $kriteria['type'];
	
	$nilai_max = max($matriks_y[$id_kriteria]);
	$nilai_min = min($matriks_y[$id_kriteria]);
	
	if($type_kriteria == 'benefit'):
		$s_i_p = $nilai_max;
		$s_i_n = $nilai_min;
	elseif($type_kriteria == 'cost'):
		$s_i_p = $nilai_min;
		$s_i_n = $nilai_max;
	endif;
	
	$solusi_ideal_positif[$id_kriteria] = $s_i_p;
	$solusi_ideal_negatif[$id_kriteria] = $s_i_n;

endforeach;


/* >>> STEP 6 ================================
 * Jarak Ideal Positif & Negatif
 * ------------------------------------------- */
$jarak_ideal_positif = array();
$jarak_ideal_negatif = array();
foreach($alternatives as $alternative):

	$id_alternative = $alternative['id_alternative'];		
	$jumlah_kuadrat_jip = 0;
	$jumlah_kuadrat_jin = 0;
	
	// Mencari penjumlahan kuadrat
	foreach($matriks_y as $id_kriteria => $nilai_alternatives):
		
		$hsl_pengurangan_jip = $nilai_alternatives[$id_alternative] - $solusi_ideal_positif[$id_kriteria];
		$hsl_pengurangan_jin = $nilai_alternatives[$id_alternative] - $solusi_ideal_negatif[$id_kriteria];
		
		$jumlah_kuadrat_jip += pow($hsl_pengurangan_jip, 2);
		$jumlah_kuadrat_jin += pow($hsl_pengurangan_jin, 2);
	
	endforeach;
	
	// Mengakarkan hasil penjumlahan kuadrat
	$akar_kuadrat_jip = sqrt($jumlah_kuadrat_jip);
	$akar_kuadrat_jin = sqrt($jumlah_kuadrat_jin);
	
	// Memasukkan ke array matriks jip & jin
	$jarak_ideal_positif[$id_alternative] = $akar_kuadrat_jip;
	$jarak_ideal_negatif[$id_alternative] = $akar_kuadrat_jin;
	
endforeach;


/* >>> STEP 7 ================================
 * Perangkingan
 * ------------------------------------------- */
$ranks = array();
foreach($alternatives as $alternative):

	$s_negatif = $jarak_ideal_negatif[$alternative['id_alternative']];
	$s_positif = $jarak_ideal_positif[$alternative['id_alternative']];	
	
	$nilai_v = $s_negatif / ($s_positif + $s_negatif);
	
	$ranks[$alternative['id_alternative']]['id_alternative'] = $alternative['id_alternative'];
	$ranks[$alternative['id_alternative']]['no_alternative'] = $alternative['no_alternative'];
	$ranks[$alternative['id_alternative']]['nilai'] = $nilai_v;
	
endforeach;

// Sort ranks by nilai_v in descending order
usort($ranks, function($a, $b) {
	return $b['nilai'] <=> $a['nilai'];
});

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
					<th>Kriteria</th>
					<?php foreach($kriterias as $kriteria ): ?>
						<th><?php echo $kriteria['nama']; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Bobot</td>
					<?php foreach($kriterias as $kriteria ): ?>
						<td><?php echo $kriteria['bobot']; ?></td>
					<?php endforeach; ?>
				</tr>
			</tbody>
		</table>		
		
		<!-- STEP 3. Matriks Ternormalisasi (R) ==================== -->
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
		
		<!-- STEP 4. Matriks Y ==================== -->
		<h3>Step 4: Matriks Y</h3>
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
							echo round($matriks_y[$id_kriteria][$id_alternative], $digit);
							echo '</td>';
						endforeach;
						?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<!-- STEP 5. Solusi Ideal Positif & Negatif ==================== -->
		<h3>Step 5: Solusi Ideal Positif & Negatif</h3>
		<table class="pure-table pure-table-striped">
			<thead>
				<tr>
					<th>Kriteria</th>
					<?php foreach($kriterias as $kriteria ): ?>
						<th><?php echo $kriteria['nama']; ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Solusi Ideal Positif</td>
					<?php foreach($solusi_ideal_positif as $id_kriteria => $nilai): ?>
						<td><?php echo round($nilai, $digit); ?></td>
					<?php endforeach; ?>
				</tr>
				<tr>
					<td>Solusi Ideal Negatif</td>
					<?php foreach($solusi_ideal_negatif as $id_kriteria => $nilai): ?>
						<td><?php echo round($nilai, $digit); ?></td>
					<?php endforeach; ?>
				</tr>
			</tbody>
		</table>
		
		<!-- STEP 6. Jarak Ideal Positif & Negatif ==================== -->
		<h3>Step 6: Jarak Ideal Positif & Negatif</h3>
		<table class="pure-table pure-table-striped">
			<thead>
				<tr>
					<th>No. alternative</th>
					<th>Jarak ke Solusi Ideal Positif (D<sup>+</sup>)</th>
					<th>Jarak ke Solusi Ideal Negatif (D<sup>-</sup>)</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($alternatives as $alternative): ?>
				<tr>
					<td><?php echo $alternative['no_alternative']; ?></td>
					<td><?php echo round($jarak_ideal_positif[$alternative['id_alternative']], $digit); ?></td>
					<td><?php echo round($jarak_ideal_negatif[$alternative['id_alternative']], $digit); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<!-- STEP 7. Perangkingan ==================== -->
		<h3>Step 7: Perangkingan</h3>
		<table class="pure-table pure-table-striped">
			<thead>
				<tr>
					<th>Peringkat</th>
					<th>No. alternative</th>
					<th>Nilai</th>
				</tr>
			</thead>
			<tbody>
				<?php 
				$no = 1;
				foreach($ranks as $rank): ?>
				<tr>
					<td><?php echo $no++; ?></td>
					<td><?php echo $rank['no_alternative']; ?></td>
					<td><?php echo round($rank['nilai'], $digit); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	</div> <!-- .main-content -->
</div> <!-- .container -->
</div> <!-- .main-content-row -->

<?php
require_once('template-parts/footer.php');
?>
