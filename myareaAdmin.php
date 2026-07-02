<?php
session_start();
include_once "database.php";
$conn = oDBConn();

// se l'utente non è loggato lo rimanda al login
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

// Verifica che sia admin
if ($_SESSION["state"] != "admin") {
    header("Location: myarea.php");
    exit;
}

// // gestione del salvataggio della funzione (quando viene inviato il form POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["salva_funzioni"])) {
    $id_utente = (int)$_POST["id_utente"];
    $admin_id = $_SESSION["id"];
    
    // Array delle funzioni POST (checkbox che vengono selezionate)
    $funzioni_selezionate = isset($_POST["funzioni"]) ? $_POST["funzioni"] : [];
    
    // Lista di tutte le possibili funzioni
    $tutte_funzioni = ['acquisti', 'ordini', 'storico', 'profilo'];
    
    // Per ogni funzione, aggiorna lo stato nel database
    foreach ($tutte_funzioni as $funzione_nome) {
   
        $abilitato = in_array($funzione_nome, $funzioni_selezionate) ? 1 : 0;
        
        // Ottieni l'id della funzione
        $sql_id_funzione = "SELECT id_funzione FROM FUNZIONI WHERE nome_funzione = ?";
        $stmt_id = $conn->prepare($sql_id_funzione);
        $stmt_id->bind_param("s", $funzione_nome);
        $stmt_id->execute();
        $result_id = $stmt_id->get_result();
        $row_id = $result_id->fetch_assoc();
        $id_funzione = $row_id["id_funzione"];
        $stmt_id->close();
        
        // Inserisci o aggiorna lo stato
        $toggleSql = "INSERT INTO UTENTI_FUNZIONI (id_utente, id_funzione, abilitato, assegnato_da) 
                      VALUES (?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE /*si controlla se esistono già dei duplicati e in caso vengono aggiornati*/
                      abilitato = ?, assegnato_da = ?, data_modifica = NOW()"; // viene stampato il tempo in cui è stata cambiata la funzione che corrisponde a quello che troviamo in 'utenti funzioni' 
        $stmt = $conn->prepare($toggleSql);
        $stmt->bind_param("iiiisi", $id_utente, $id_funzione, $abilitato, $admin_id, $abilitato, $admin_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Messaggio di conferma e redirect dove la pagina vinene reidirizzata
    echo "<script>alert('✅ Funzioni salvate con successo!'); window.location.href='myareaAdmin.php';</script>";
    exit;
}

// Query per prendere tutti gli utenti ordinati per ID
$sql = "SELECT id_utente, utente, stato FROM TB_UTENTI ORDER BY id_utente ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Admin - Gestione Funzioni</title>
    <link rel="stylesheet" href="style.css">
  
</head>
<body class="bodyadmin">
    <link rel="stylesheet" href="style.css"> 
    <img border=1 src="immagini/logo.png" width="100" height="100" class="dx">
    
    <div class="container">
        <div class="welcome">
            <h2>👑 Ciao Admin, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
            <p>Sei loggato come: <strong>AMMINISTRATORE</strong></p>
        </div>

        <div class="box">
            <h2>📋 Lista Utenti Registrati</h2>
            <div class="info-message">
                💡 <strong>Istruzioni:</strong> Seleziona le checkbox delle funzioni che vuoi abilitare per ogni utente, poi clicca su 💾 SALVA.
            </div>
            
            <table class="tableAdmin">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Stato</th>
                        <th class="azioni-header">🎛️ Funzioni Abilitate (acquisti, ordini, storico, profilo)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Recupera le funzioni già abilitate per questo utente
                            $funzioni_abilitate = [];
                            $sql_funzioni = "SELECT f.nome_funzione 
                                            FROM UTENTI_FUNZIONI uf
                                            JOIN FUNZIONI f ON uf.id_funzione = f.id_funzione
                                            WHERE uf.id_utente = ? AND uf.abilitato = 1";
                            $stmt_funzioni = $conn->prepare($sql_funzioni);
                            $stmt_funzioni->bind_param("i", $row["id_utente"]);
                            $stmt_funzioni->execute();
                            $result_funzioni = $stmt_funzioni->get_result();
                            while ($f = $result_funzioni->fetch_assoc()) {
                                $funzioni_abilitate[] = $f["nome_funzione"];
                            }
                            $stmt_funzioni->close();
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row["id_utente"]) . "</td>";
                            echo "<td><strong>" . htmlspecialchars($row["utente"]) . "</strong></td>";
                            echo "<td>";
                            if ($row["stato"] == "admin") {
                                echo "<span class='badge attiva'>👑 Admin</span>";
                            } else {
                                echo "<span class='badge nonattiva'>👤 User</span>";
                            }
                            echo "</td>";
                            
                            // Colonn
                            echo "<td>";
                            echo "<form method='POST' class='form-funzioni' onsubmit='return confermaSalvataggio(event, this)'>";
                            echo "<input type='hidden' name='id_utente' value='" . $row["id_utente"] . "'>";
                            echo "<input type='hidden' name='salva_funzioni' value='1'>";
                            
                            echo "<div class='funzioni-checkbox'>";
                            
                            // Checkbox per acquisti
                            $checked_acquisti = in_array("acquisti", $funzioni_abilitate) ? "checked" : "";
                            echo "<div class='funzione-item " . ($checked_acquisti ? "abilitata" : "") . "'>";
                            echo "<input type='checkbox' name='funzioni[]' value='acquisti' id='acquisti_" . $row["id_utente"] . "' $checked_acquisti onchange='cambiaColore(this)'>";
                            echo "<label for='acquisti_" . $row["id_utente"] . "'>🛒 Acquisti</label>";
                            echo "</div>";
                            
                            // Checkbox per ordini
                            $checked_ordini = in_array("ordini", $funzioni_abilitate) ? "checked" : "";
                            echo "<div class='funzione-item " . ($checked_ordini ? "abilitata" : "") . "'>";
                            echo "<input type='checkbox' name='funzioni[]' value='ordini' id='ordini_" . $row["id_utente"] . "' $checked_ordini onchange='cambiaColore(this)'>";
                            echo "<label for='ordini_" . $row["id_utente"] . "'>📦 Ordini</label>";
                            echo "</div>";
                            
                            // Checkbox per storico
                            $checked_storico = in_array("storico", $funzioni_abilitate) ? "checked" : "";
                            echo "<div class='funzione-item " . ($checked_storico ? "abilitata" : "") . "'>";
                            echo "<input type='checkbox' name='funzioni[]' value='storico' id='storico_" . $row["id_utente"] . "' $checked_storico onchange='cambiaColore(this)'>";
                            echo "<label for='storico_" . $row["id_utente"] . "'>📜 Storico</label>";
                            echo "</div>";
                            
                            // Checkbox per profilo
                            $checked_profilo = in_array("profilo", $funzioni_abilitate) ? "checked" : "";
                            echo "<div class='funzione-item " . ($checked_profilo ? "abilitata" : "") . "'>";
                            echo "<input type='checkbox' name='funzioni[]' value='profilo' id='profilo_" . $row["id_utente"] . "' $checked_profilo onchange='cambiaColore(this)'>";
                            echo "<label for='profilo_" . $row["id_utente"] . "'>👤 Profilo</label>";
                            echo "</div>";
                            
                            echo "<button type='submit' class='btn-salva'>💾 SALVA</button>";
                            echo "</div>";
                            echo "</form>";
                            echo "</td>";
                            
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>Nessun utente trovato</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Funzione per cambiare colore al div quando si seleziona/deseleziona una checkbox
        function cambiaColore(checkbox) {
            var divItem = checkbox.closest('.funzione-item');
            if (checkbox.checked) {
                divItem.classList.add('abilitata');
            } else {
                divItem.classList.remove('abilitata');
            }
        }
        
        // Funzione di conferma prima del salvataggio
        function confermaSalvataggio(event, form) {
            event.preventDefault();
            var conferma = confirm("⚠️ Sei sicuro di voler salvare queste impostazioni per questo utente?");
            if (conferma) {
                form.submit();
            }
            return false;
        }
    </script>
    
</body>
</html>

<?php $conn->close(); ?>