<?php



// Avvia la sessione per ricordare chi è loggato
session_start();

// Includo il file con la connessione al database
include_once "database.php";

// CONTROLLO LOG UTENTE
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

//  DATI DELL'UTENTE DAL DATABASE

$conn = oDBConn();

$id_utente = $_SESSION["id"];
$username = $_SESSION["username"];

// Query per prendere TUTTI i dati dell'utente
$sql = "SELECT id_utente, utente, pwd, stato, descrizione, foto_profilo, data_nascita 
        FROM TB_UTENTI 
        WHERE id_utente = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_utente);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<h3>Errore: Utente non trovato!</h3>";
    echo "<a href='myarea.php'>← Torna indietro</a>";
    exit;
}

$utente = $result->fetch_assoc();
$stmt->close();


// RECUPERO LE FUNZIONI ABILITATE DELL'UTENTE

$funzioni_utente = []; // Inizializzo l'array VUOTO

$sql_funzioni = "SELECT f.nome_funzione, f.icona, f.descrizione 
                 FROM UTENTI_FUNZIONI uf
                 JOIN FUNZIONI f ON uf.id_funzione = f.id_funzione
                 WHERE uf.id_utente = ? AND uf.abilitato = 1";

$stmt_funzioni = $conn->prepare($sql_funzioni);
$stmt_funzioni->bind_param("i", $id_utente);
$stmt_funzioni->execute();
$result_funzioni = $stmt_funzioni->get_result();

while ($row = $result_funzioni->fetch_assoc()) {
    $funzioni_utente[] = $row; // Aggiungo le funzioni all'array
}
$stmt_funzioni->close();


//  GESTISCO GLI AGGIORNAMENTI DEL PROFILO

$messaggio = "";
$errore = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // CASO 1: Aggiornare la descrizione
    if (isset($_POST["aggiorna_descrizione"])) {
        $nuova_descrizione = $_POST["descrizione"] ?? "";
        
        $sql_update = "UPDATE TB_UTENTI SET descrizione = ? WHERE id_utente = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $nuova_descrizione, $id_utente);
        
        if ($stmt_update->execute()) {
            $messaggio = "✅ Descrizione aggiornata!";
            $utente["descrizione"] = $nuova_descrizione;
        } else {
            $errore = "❌ Errore, riprova.";
        }
        $stmt_update->close();
    }
    
    // CASO 2: Cambiare password
    elseif (isset($_POST["aggiorna_password"])) {
        $vecchia = $_POST["vecchia_password"] ?? "";
        $nuova = $_POST["nuova_password"] ?? "";
        $conferma = $_POST["conferma_password"] ?? "";
        
        if ($vecchia != $utente["pwd"]) {
            $errore = "❌ La vecchia password non è corretta!";
        } elseif (strlen($nuova) < 4) {
            $errore = "❌ La password deve avere almeno 4 caratteri!";
        } elseif ($nuova != $conferma) {
            $errore = "❌ Le password non coincidono!";
        } else {
            $sql_update = "UPDATE TB_UTENTI SET pwd = ? WHERE id_utente = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $nuova, $id_utente);
            
            if ($stmt_update->execute()) {
                $messaggio = "✅ Password cambiata!";
                $utente["pwd"] = $nuova;
            } else {
                $errore = "❌ Errore nel cambio password.";
            }
            $stmt_update->close();
        }
    }
    
    // CASO 3: Caricare una foto profilo
    elseif (isset($_POST["aggiorna_foto"]) && isset($_FILES['foto_profilo'])) {
        $file = $_FILES['foto_profilo'];
        
        if ($file['error'] === 0) {
            $cartella = "uploads/";
            if (!is_dir($cartella)) {
                mkdir($cartella, 0777, true);
            }
            
            $estensione = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $permesse = ["jpg", "jpeg", "png", "webp"];
            
            if (!in_array($estensione, $permesse)) {
                $errore = "❌ Solo JPG, PNG o WEBP!";
            } else {
                $nome_file = time() . "_" . basename($file['name']);
                $percorso = $cartella . $nome_file;
                
                if (move_uploaded_file($file['tmp_name'], $percorso)) {
                    $sql_update = "UPDATE TB_UTENTI SET foto_profilo = ? WHERE id_utente = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("si", $percorso, $id_utente);
                    
                    if ($stmt_update->execute()) {
                        $messaggio = "✅ Foto profilo aggiornata!";
                        $utente["foto_profilo"] = $percorso;
                    } else {
                        $errore = "❌ Errore nel salvataggio.";
                    }
                    $stmt_update->close();
                } else {
                    $errore = "❌ Errore nel caricamento della foto.";
                }
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il mio Profilo - <?php echo htmlspecialchars($username); ?></title>
     <link rel="stylesheet" href="style.css">
   
</head>
<body class="profilo-page">

<div class="profilo-box">
    
    <!-- HEADER -->
    <div class="profilo-header">
        <h1>
            <img src="immagini/logo.png" width="50" height="50">
            Il Mio Profilo
        </h1>
        <a href="myarea.php" class="torna-btn">← Torna alla mia area</a>
    </div>
    
    <!-- MESSAGGI -->
    <?php if (!empty($messaggio)): ?>
        <div class="messaggio successo"><?php echo $messaggio; ?></div>
    <?php endif; ?>
    <?php if (!empty($errore)): ?>
        <div class="messaggio errore"><?php echo $errore; ?></div>
    <?php endif; ?>
    
    <!-- CONTENUTO PRINCIPALE -->
    <div class="profilo-due-colonne">
        
        <!-- COLONNA SINISTRA - INFO UTENTE -->
        <div class="profilo-sidebar">
            
            <!-- FOTO PROFILO -->
            <?php if (!empty($utente["foto_profilo"]) && file_exists($utente["foto_profilo"])): ?>
                <img src="<?php echo htmlspecialchars($utente["foto_profilo"]); ?>" alt="Foto" class="profilo-foto">
            <?php else: ?>
                <div class="profilo-foto-placeholder">👤</div>
            <?php endif; ?>
            
            <!-- NOME UTENTE -->
            <h2 class="profilo-nome"><?php echo htmlspecialchars($utente["utente"]); ?></h2>
            
            <!-- STATO (ADMIN/USER) -->
            <span class="profilo-badge <?php echo $utente["stato"] == "admin" ? "admin" : "user"; ?>">
                <?php echo $utente["stato"] == "admin" ? "👑 Amministratore" : "👤 Utente normale"; ?>
            </span>
            
            <!-- FUNZIONI ABILITATE -->
            <div class="profilo-funzioni">
                <h4>📋 Le mie funzioni</h4>
                <div class="profilo-lista-funzioni">
                    <?php if (count($funzioni_utente) > 0): ?>
                        <?php foreach ($funzioni_utente as $funzione): ?>
                            <span class="profilo-funzione-tag">
                                <?php echo htmlspecialchars($funzione["icona"]); ?> 
                                <?php echo ucfirst(htmlspecialchars($funzione["nome_funzione"])); ?>
                            </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color: #9ca3af;">Nessuna funzione abilitata</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- COLONNA DESTRA - FORM MODIFICA -->
        <div class="profilo-contenuto">
            
            <!-- Card 1: INFORMAZIONI PERSONALI -->
            <div class="profilo-card">
                <h3>📋 Le mie informazioni</h3>
                <div class="profilo-info-grid">
                    <div class="profilo-info-item">
                        <span class="profilo-info-label">🆔 ID Utente</span>
                        <span class="profilo-info-value"><?php echo $utente["id_utente"]; ?></span>
                    </div>
                    <div class="profilo-info-item">
                        <span class="profilo-info-label">👤 Username</span>
                        <span class="profilo-info-value"><?php echo htmlspecialchars($utente["utente"]); ?></span>
                    </div>
                    <div class="profilo-info-item">
                        <span class="profilo-info-label">🔒 Password</span>
                        <span class="profilo-info-value">••••••••</span>
                    </div>
                    <?php if (!empty($utente["data_nascita"]) && $utente["data_nascita"] != "0000-00-00"): ?>
                    <div class="profilo-info-item">
                        <span class="profilo-info-label">🎂 Data di nascita</span>
                        <span class="profilo-info-value"><?php echo date("d/m/Y", strtotime($utente["data_nascita"])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="profilo-info-item">
                        <span class="profilo-info-label">📝 La mia descrizione</span>
                        <span class="profilo-info-value"><?php echo !empty($utente["descrizione"]) ? htmlspecialchars($utente["descrizione"]) : "<em>Nessuna descrizione</em>"; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Card 2: MODIFICA DESCRIZIONE -->
            <div class="profilo-card">
                <h3>✏️ Modifica la mia descrizione</h3>
                <form method="POST">
                    <div class="profilo-form-group">
                        <textarea name="descrizione" placeholder="Scrivi qualcosa su di te..."><?php echo htmlspecialchars($utente["descrizione"] ?? ""); ?></textarea>
                    </div>
                    <button type="submit" name="aggiorna_descrizione" class="profilo-btn">💾 Salva descrizione</button>
                </form>
            </div>
            
            <!-- Card 3: CAMBIA PASSWORD -->
            <div class="profilo-card">
                <h3>🔐 Cambia la mia password</h3>
                <form method="POST">
                    <div class="profilo-form-group">
                        <label>Vecchia password</label>
                        <input type="password" name="vecchia_password" placeholder="Inserisci la password attuale" required>
                    </div>
                    <div class="profilo-form-group">
                        <label>Nuova password</label>
                        <input type="password" name="nuova_password" placeholder="Almeno 4 caratteri" required>
                    </div>
                    <div class="profilo-form-group">
                        <label>Conferma nuova password</label>
                        <input type="password" name="conferma_password" placeholder="Riscrivi la nuova password" required>
                    </div>
                    <button type="submit" name="aggiorna_password" class="profilo-btn">🔄 Cambia password</button>
                </form>
            </div>
            
            <!-- Card 4: CAMBIA FOTO PROFILO -->
            <div class="profilo-card">
                <h3>📸 Cambia la mia foto profilo</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="profilo-form-group">
                        <label>Scegli una foto dal tuo computer</label>
                        <input type="file" name="foto_profilo" accept=".jpg,.jpeg,.png,.webp" required>
                        <small style="color: #6b7280; display: block; margin-top: 5px;">Formati accettati: JPG, JPEG, PNG, WEBP</small>
                    </div>
                    <button type="submit" name="aggiorna_foto" class="profilo-btn">📤 Carica nuova foto</button>
                </form>
            </div>
            
        </div>
    </div>
    
    <!-- FOOTER -->
    <div class="profilo-footer">
        <p>© 2024 NEW ERA - Il tuo profilo personale</p>
    </div>
    
</div>

</body>
</html>