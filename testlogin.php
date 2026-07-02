<?php
session_start();
include_once "database.php";
$ODBConn = oDBConn();

$username = $_POST["user"];
$password = $_POST["pass"];

/*viene creta una query con l'utente associato, I punti interrogativi ? sono placeholder che servono a preparare la query , riducendo il rischio di SQL injection 'esterni'*/
$sql = "SELECT id_utente, utente, stato FROM TB_UTENTI WHERE utente = ? AND pwd = ?";
$stmt = $ODBConn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$resultato = $stmt->get_result();

if ($resultato && $resultato->num_rows == 1) { // qui viene controllato che la query abbia prodotto almeno una riga associato cioè un'utente
    $utenteLoggato = $resultato->fetch_assoc(); // vengono estratti i dati di quell'utente

    $_SESSION["username"] = $utenteLoggato["utente"];
    $_SESSION["id"] = $utenteLoggato["id_utente"];
    $_SESSION["state"] = $utenteLoggato["stato"];

    //LOG DELL'ACCESSO
    $logSql = "INSERT INTO log_accessi (id_utente, data_ora, ip_address) VALUES (?, NOW(), ?)";
    $logStmt = $ODBConn->prepare($logSql);
    $ip = $_SERVER['REMOTE_ADDR']; // qui viene recuperato l'indirizzo IP dell'utente associato
    $logStmt->bind_param("is", $utenteLoggato["id_utente"], $ip); // indica che il primo parametro è intero (i) e il secondo è stringa
    $logStmt->execute();
    $logStmt->close();

    // qui vengono verificate le funzioni degli user abilitate dagli admin, così da associarli alla pagina
    if ($utenteLoggato["stato"] != "admin") {
        $sqlFunzioni = "SELECT COUNT(*) as num_funzioni 
        FROM UTENTI_FUNZIONI 
        WHERE id_utente = ? AND abilitato = 1";
        $stmtFunzioni = $ODBConn->prepare($sqlFunzioni);
        $stmtFunzioni->bind_param("i", $utenteLoggato["id_utente"]);
        $stmtFunzioni->execute();
        $resultFunzioni = $stmtFunzioni->get_result();
        $rowFunzioni = $resultFunzioni->fetch_assoc();
        $numFunzioni = $rowFunzioni["num_funzioni"];
        $stmtFunzioni->close();
        
        // Se l'utente non ha NESSUNA funzione abilitata → blocco l'accesso
        if ($numFunzioni == 0) {
            // Distruggo la sessione appena creata
            session_destroy();
            
            // Mostro messaggio di errore
            echo "<!DOCTYPE html>";
            echo "<html>";
            echo "<head>";
            echo "<link rel='stylesheet' href='style.css'>";
            echo "<title>Accesso Negato</title>";
            echo "<style>";
            echo "body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: Arial, sans-serif; }";
            echo ".accesso-negato { background: white; border-radius: 20px; padding: 40px; text-align: center; max-width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }";
            echo ".accesso-negato .icona { font-size: 5rem; margin-bottom: 20px; }";
            echo ".accesso-negato h1 { color: #dc2626; margin-bottom: 15px; }";
            echo ".accesso-negato p { color: #4b5563; margin-bottom: 10px; line-height: 1.6; }";
            echo ".btn { display: inline-block; margin-top: 20px; padding: 12px 25px; background: #3b82f6; color: white; text-decoration: none; border-radius: 10px; }";
            echo ".btn:hover { background: #2563eb; }";
            echo "</style>";
            echo "</head>";
            echo "<body>";
            echo "<div class='accesso-negato'>";
            echo "<div class='icona'>🚫</div>";
            echo "<h1>Accesso Negato</h1>";
            echo "<p>Benvenuto <strong>" . htmlspecialchars($username) . "</strong>,</p>";
            echo "<p>Il tuo account non ha <strong>nessuna funzione abilitata</strong> dall'amministratore.</p>";
            echo "<p>Contatta l'amministratore per abilitare almeno una funzione (acquisti, ordini, storico o profilo).</p>";
            echo "<a href='login.php' class='btn'>← Torna al login</a>";
            echo "</div>";
            echo "</body>";
            echo "</html>";
            exit;
        }
    }

    // Se arrivo qui, l'accesso è consentito
    if ($utenteLoggato["stato"] == "admin") {
        header("Location: myareaAdmin.php");
    } else {
        header("Location: myarea.php");
    }
    exit;
    
} else {
    echo "<h3>❌ Errore: Username o Password errati!</h3>";
    echo "<a href='login.php'>← Torna al login</a>";
}

$stmt->close();
$ODBConn->close();
?>