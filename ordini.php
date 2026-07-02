<?php
session_start();
include_once "database.php";
$conn = oDBConn();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

$id_utente = $_SESSION["id"];

// Controllo abilitazione pagina
$sql_check = "SELECT uf.abilitato 
              FROM UTENTI_FUNZIONI uf 
              JOIN FUNZIONI f ON uf.id_funzione = f.id_funzione 
              WHERE uf.id_utente = ? AND f.nome_funzione = 'ordini' AND uf.abilitato = 1";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $id_utente);
$stmt_check->execute();
$res_check = $stmt_check->get_result();

if ($res_check->num_rows == 0) {
    header("Location: myarea.php?errore=Non+sei+abilitato+a+questa+funzione");
    exit;
}
$stmt_check->close();

// Recupero gli ordini dell'utente connesso
$sql_ordini = "SELECT id_ordine, numero_ordine, data_ordine, totale, stato FROM TB_ORDINI WHERE id_utente = ? ORDER BY data_ordine DESC";
$stmt_ordini = $conn->prepare($sql_ordini);
$stmt_ordini->bind_param("i", $id_utente);
$stmt_ordini->execute();
$result_ordini = $stmt_ordini->get_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>I miei Ordini</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "menu.php"; ?>
    <img src="immagini/logo.png" width="100" height="100" class="dx">
    
    <div class="container" style="margin-top: 40px;">
        <div class="welcome" style="background-color: var(--secondary-color);">
            <h2>📦 Gestione Ordini</h2>
            <p>Visualizza lo stato di spedizione e lo storico dei tuoi ordini.</p>
        </div>

        <div class="box">
            <h2>📋 Elenco dei tuoi ordini</h2>
            <table class="tableAdmin">
                <thead>
                    <tr>
                        <th>ID Ordine</th>
                        <th>Numero Identificativo</th>
                        <th>Data Ordine</th>
                        <th>Totale</th>
                        <th>Stato Spedizione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_ordini && $result_ordini->num_rows > 0): ?>
                        <?php while ($ordine = $result_ordini->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $ordine["id_ordine"]; ?></td>
                                <td><strong><?php echo htmlspecialchars($ordine["numero_ordine"]); ?></strong></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($ordine["data_ordine"])); ?></td>
                                <td><strong>€ <?php echo number_format($ordine["totale"], 2, ',', '.'); ?></strong></td>
                                <td>
                                    <?php 
                                    $stato = strtolower($ordine["stato"]);
                                    if ($stato == 'consegnato') {
                                        echo "<span class='badge' style='background-color: #d1fae5; color: #065f46;'>🟢 Consegnato</span>";
                                    } elseif ($stato == 'in lavorazione') {
                                        echo "<span class='badge' style='background-color: #fef3c7; color: #d97706;'>🟡 In lavorazione</span>";
                                    } else {
                                        echo "<span class='badge nonattiva'>⚪ Spedito</span>";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">Non hai ancora effettuato nessun ordine.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php 
$stmt_ordini->close();
$conn->close(); 
?>