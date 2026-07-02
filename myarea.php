<?php
session_start(); // viene avviata la sessione per mantenere i dati utenti 
include_once "database.php";
$conn = oDBConn(); // connesione al database

// viene controllato se l'utente è loggato
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

$id_utente = $_SESSION["id"];  // è una variabile associata all'id dell'utente loggato 
$username = $_SESSION["username"]; // in ugual modo, con l'username 

$funzioni_abilitate = []; // viene creato un array vuoto che conterrà le funzioni a cui l'utente avrà accesso
$sql_funzioni = "SELECT f.nome_funzione, f.percorso, f.icona, f.descrizione 
                 FROM UTENTI_FUNZIONI uf
                 JOIN FUNZIONI f ON uf.id_funzione = f.id_funzione
                 WHERE uf.id_utente = ? AND uf.abilitato = 1 
                 ORDER BY f.ordine_visualizzazione ASC";
$stmt = $conn->prepare($sql_funzioni);
$stmt->bind_param("i", $id_utente);
$stmt->execute();
$result_funzioni = $stmt->get_result();

while ($row = $result_funzioni->fetch_assoc()) {
    $funzioni_abilitate[] = $row;
}
$stmt->close();
$conn->close();
?>

<html>
<head>
    <link rel="stylesheet" href="style.css">
    <title>myarea</title>
</head>
<body>
    <?php include "menu.php"; ?>
    <img border="1" src="immagini/logo.png" width="100" height="100" class="dx">
    
    <div class="dashboard-user">
        <div class="welcome-card">
            <h2>👋 Ciao, <?php echo htmlspecialchars($username); ?>!</h2>
            <p>Benvenuto nella tua area personale. Di seguito le funzioni che ti sono state abilitate.</p>
        </div>
   
        
        <?php if (count($funzioni_abilitate) > 0): ?>
            <div class="grid-funzioni">
                <?php foreach ($funzioni_abilitate as $funzione): ?>
                    <a href="<?php echo htmlspecialchars($funzione["percorso"]); ?>" class="card-funzione">
                        <div class="icona-funzione"><?php echo htmlspecialchars($funzione["icona"]); ?></div>
                        <h3><?php echo ucfirst(htmlspecialchars($funzione["nome_funzione"])); ?></h3>
                        <p><?php echo htmlspecialchars($funzione["descrizione"]); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="nessuna-funzione">
                <span>🔒</span>
                <h3>Nessuna funzione abilitata</h3>
                <p>Al momento non hai accesso a nessuna funzionalità.</p>
                <p>Contatta l'amministratore per abilitare le tue funzioni.</p>
                <a href="logout.php" class="btn-logout">🚪 Esci</a>
            </div>
        <?php endif; ?>
        
        <div class="footer-info">
            <p>© 2024 NEW ERA </p>
        </div>
    </div>
</body>
</html>