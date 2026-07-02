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
              WHERE uf.id_utente = ? AND f.nome_funzione = 'storico' AND uf.abilitato = 1";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $id_utente);
$stmt_check->execute();
$res_check = $stmt_check->get_result();

if ($res_check->num_rows == 0) {
    header("Location: myarea.php?errore=Non+sei+abilitato+a+questa+funzione");
    exit;
}
$stmt_check->close();

// Recupera lo storico delle attività per questo utente
$sql_storico = "SELECT id_attivita, azione, dettagli, data_ora, ip_address FROM TB_STORICO_ATTIVITA WHERE id_utente = ? ORDER BY data_ora DESC";
$stmt_storico = $conn->prepare($sql_storico);
$stmt_storico->bind_param("i", $id_utente);
$stmt_storico->execute();
$result_storico = $stmt_storico->get_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Storico Attività</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "menu.php"; ?>
    <img src="immagini/logo.png" width="100" height="100" class="dx">
    
    <div class="container" style="margin-top: 40px;">
        <div class="welcome" style="background-color: #6366f1;">
            <h2>📜 Storico Attività</h2>
            <p>Traccia le operazioni effettuate sul tuo account per motivi di sicurezza.</p>
        </div>

        <div class="box">
            <h2>🕒 Registro delle tue attività</h2>
            <table class="tableAdmin">
                <thead>
                    <tr>
                        <th>ID Attività</th>
                        <th>Azione</th>
                        <th>Dettagli operazione</th>
                        <th>Data e Ora</th>
                        <th>Indirizzo IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_storico && $result_storico->num_rows > 0): ?>
                        <?php while ($act = $result_storico->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $act["id_attivita"]; ?></td>
                                <td><span class="badge" style="background-color: #e0e7ff; color: #4338ca;"><?php echo htmlspecialchars($act["azione"]); ?></span></td>
                                <td><?php echo htmlspecialchars($act["dettagli"]); ?></td>
                                <td><?php echo date("d/m/Y H:i:s", strtotime($act["data_ora"])); ?></td>
                                <td><code style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:0.85rem;"><?php echo htmlspecialchars($act["ip_address"]); ?></code></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;">Nessuna attività registrata in questa sessione.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php 
$stmt_storico->close();
$conn->close(); 
?>