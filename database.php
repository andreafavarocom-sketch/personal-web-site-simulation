<?php
function oDBConn() {
	$DBhost = "localhost";
	$DBuser = "a.favaron";
	$DBpass = "a.favaron407";  // INSERIRE LA VOSTRA PASSWORD
	$DBName = "a.favaron";
//echo ("CONN: \".$DBhost.\", \".$DBuser.\", \".$DBName);
//echo "DB-Username:$DBuser - Password:$DBpass <BR/><BR/>";
	$oDBConn = 0;
	$oDBConn = mysqli_connect($DBhost,$DBuser, $DBpass, $DBName) or die(mysqli_connect_error());
	if (!$oDBConn)
	 { 
	$oDBConn = 1; // Errore: connessione al DB fallita
	 } 
	return $oDBConn;
}

function query($conn, $sql, $tipi = "", ...$parametri) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Errore preparazione query: " . $conn->error);
    }
    
    if (!empty($tipi) && !empty($parametri)) {
        $stmt->bind_param($tipi, ...$parametri);
    }
    
    $stmt->execute();
    return $stmt;
}


function registraLogAccesso($conn, $id_utente) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $sql = "INSERT INTO log_accessi (id_utente, data_ora, ip_address) VALUES (?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id_utente, $ip);
    $stmt->execute();
    $stmt->close();
}

// Registra le attività generiche dell'utente (es. inserimento a carrello, ordini)
 // all'interno della tabella TB_STORICO_ATTIVITA per la pagina Storico.
 
function registraAttivita($conn, $id_utente, $azione, $dettagli) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $sql = "INSERT INTO TB_STORICO_ATTIVITA (id_utente, azione, dettagli, data_ora, ip_address) VALUES (?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("isss", $id_utente, $azione, $dettagli, $ip);
        $stmt->execute();
        $stmt->close();
    }
}
?>