<!DOCTYPE html>
<head>
	<meta http-equiv="x-ua-compatible" content="ie=edge" />
	<meta charset="UTF-8" />
	<title><?php
		if(isset($judul_page)) {
			echo $judul_page;
		}
	?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
	<link rel="stylesheet" href="stylesheets/style.css">
	<script type="text/javascript" src="js/jquery-1.11.2.min.js"></script>	
	<script type="text/javascript" src="js/superfish.min.js"></script>	
	<script type="text/javascript" src="js/main.js"></script>	
</head>
<body>
	<div id="page">
	
	<header id="header">
		<div class="container clearfix">
			<div id="logo-wrap">
				<h1 id="logo"><a href="index.php"><img src="images/logo1.png" alt=""></a></h1>
			</div>
			
			<div id="header-content" class="clearfix">
				<nav id="nav">
					<ul class="sf-menu">
							</li>						
							<li><a href="list-kriteria.php">Kriteria</a>
								<ul>
									<li><a href="list-kriteria.php">List Kriteria</a></li>
									<li><a href="tambah-kriteria.php">Tambah Kriteria</a></li>
								</ul>
							</li>
					
							<li><a href="list-alternative.php">Alternative</a>
								<ul>
									<li><a href="list-alternative.php">List Alternative</a></li>
									<li><a href="tambah-alternative.php">Tambah Alternative</a></li>
								</ul>
							</li>
						<li><a href="ranking-topsis.php">Ranking</a>
							<ul>
								<li><a href="ranking-topsis.php">Topsis</a></li>
								<li><a href="ranking-saw.php">SAW</a></li>
								<li><a href="ranking-kombinasi.php">Kombinasi</a></li>
							</ul>
						</li>
					</ul>
				</nav>
				
			</div>
		</div>
	</header>
	
	<div id="main">