<?php


// Avvia la sessione per poter usare variabili di sessione più avanti
session_start();

// Include il menu di navigazione del sito
include "menu.php";

// Include le funzioni per il database
include_once "database.php";

// COLLEGA AL DATABASE
// oDBConn() è una funzione che restituisce la connessione al database
$oDBConn = oDBConn();



// $_POST contiene i dati inviati dal form di registrazione
$username = $_POST["user"];           // Nome utente scelto
$password = $_POST["pass"];           // Password inserita
$confermapassword = $_POST["passc"];  // Conferma password
$state = $_POST["role"];              // Ruolo: admin o user
$data_di_nascita = $_POST["datanascita"];  // CORREZIONE: data di nascita


if (isset($_POST["bio"])) {
    $descrizione = $_POST["bio"];
} else {
    $descrizione = "";
}



if ($password != $confermapassword) {
    echo "<h3>Errore: le password non coincidono!</h3>";
    die();  // Ferma tutto qui, non continuare
}




$checkSql = "SELECT * FROM TB_UTENTI WHERE utente = ?";
$checkStmt = $oDBConn->prepare($checkSql);


$checkStmt->bind_param("s", $username);  

$checkStmt->execute();


$risultato = $checkStmt->get_result();


if ($risultato->num_rows > 0) {
    echo "<h3>Errore: lo username '" . $username . "' è già registrato.</h3>";
    die();
}


$checkStmt->close();



$foto_profilo = null;


if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
    
    // Nome della cartella dove salvare le foto
    $cartella = "uploads/";
    

    if (!is_dir($cartella)) {
        // mkdir = make directory (crea cartella)
        // 0777 sono i permessi ( leggere/scrivere)
        // true = crea anche le cartelle padre se non esistono
        mkdir($cartella, 0777, true);
    }
    
    //estensione del file (es. .jpg, .png)
    // strtolower = tutto in minuscolo
    // pathinfo = estrae info dal percorso del file
    $estensione = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    
    // Lista delle estensioni permesse
    $estensioni_consentite = ["jpg", "jpeg", "png", "webp"];
    
    // Se l'estensione non è nella lista, errore
    if (!in_array($estensione, $estensioni_consentite)) {
        echo "<h3>Errore: formato immagine non consentito.</h3>";
        echo "<p>Solo: jpg, jpeg, png, webp</p>";
        die();
    }
    
    
    $nomeFile = time() . "_" . basename($_FILES['foto']['name']);

    $percorsoCompleto = $cartella . $nomeFile;
    

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $percorsoCompleto)) {
        $foto_profilo = $percorsoCompleto;  // Salvo il percorso nel database
    } else {
        echo "<h3>Errore durante il caricamento della foto.</h3>";
        echo "<p>Controlla che la cartella 'uploads' esista e sia scrivibile.</p>";
        die();
    }
}


// Se la descrizione è vuota, la salvo come stringa vuota
if ($descrizione == "") {
    $descrizione = "";
}


$sql = "INSERT INTO TB_UTENTI (utente, pwd, stato, descrizione, foto_profilo, data_nascita)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $oDBConn->prepare($sql);


if (!$stmt) {
    echo "<h3>Errore nella preparazione della query:</h3>";
    echo $oDBConn->error;
    die();
}



$stmt->bind_param("ssssss", $username, $password, $state, $descrizione, $foto_profilo, $data_di_nascita);

// esecuzione db 

if ($stmt->execute()) {
    // risultato
    echo "<h3>Registrazione completata!</h3>";
    echo "<p>Benvenuto <strong>" . $username . "</strong>!</p>";
    echo "<p>Ora puoi <a href='login.php'>accedere</a>.</p>";
} else {
    // Fallimento: mostro l'errore del database
    echo "<h3>Errore durante la registrazione:</h3>";
    echo $stmt->error;
    die();
}



//chiusura statemant 
$stmt->close();

// chiusura connessione db
$oDBConn->close();

?>
