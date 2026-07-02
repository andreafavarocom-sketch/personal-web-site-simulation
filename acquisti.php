<?php
session_start();
include_once "database.php";

// Controllo login
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

$conn = oDBConn();
$id_utente = $_SESSION["id"];
$message = "";
$error = "";

// Funzione per ottenere i prodotti dal carrello
function getCarrello($conn, $id_utente) {
    $sql = "SELECT c.id_carrello, c.id_prodotto, c.quantita, p.nome, p.prezzo, p.immagine 
            FROM TB_CARRELLO c
            JOIN TB_PRODOTTI p ON c.id_prodotto = p.id_prodotto
            WHERE c.id_utente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_utente);
    $stmt->execute();
    $result = $stmt->get_result();
    $carrello = [];
    while ($row = $result->fetch_assoc()) {
        $carrello[] = $row;
    }
    $stmt->close();
    return $carrello;
}

// Funzione per ottenere tutti i prodotti (da mostrare nel catalogo)
function getProdotti($conn) {
    $sql = "SELECT * FROM TB_PRODOTTI WHERE disponibile = 1 ORDER BY nome";
    $result = $conn->query($sql);
    $prodotti = [];
    while ($row = $result->fetch_assoc()) {
        $prodotti[] = $row;
    }
    return $prodotti;
}

// Gestione azioni POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Aggiungi al carrello
    if (isset($_POST["azione"]) && $_POST["azione"] == "aggiungi") {
        $id_prodotto = (int)$_POST["id_prodotto"];
        $quantita = (int)$_POST["quantita"];
        
        // Controlla se il prodotto è già nel carrello
        $check_sql = "SELECT id_carrello, quantita FROM TB_CARRELLO 
                      WHERE id_utente = ? AND id_prodotto = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $id_utente, $id_prodotto);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Aggiorna quantità
            $nuova_quantita = $row["quantita"] + $quantita;
            $update_sql = "UPDATE TB_CARRELLO SET quantita = ? WHERE id_carrello = ?";
            $stmt2 = $conn->prepare($update_sql);
            $stmt2->bind_param("ii", $nuova_quantita, $row["id_carrello"]);
            $stmt2->execute();
            $stmt2->close();
            $message = "Prodotto aggiornato nel carrello!";
        } else {
            // Inserisci nuovo prodotto
            $insert_sql = "INSERT INTO TB_CARRELLO (id_utente, id_prodotto, quantita) VALUES (?, ?, ?)";
            $stmt2 = $conn->prepare($insert_sql);
            $stmt2->bind_param("iii", $id_utente, $id_prodotto, $quantita);
            $stmt2->execute();
            $stmt2->close();
            $message = "Prodotto aggiunto al carrello!";
        }
        $stmt->close();
        
        // Registra attività
        registraAttivita($conn, $id_utente, "AGGIUNTA_CARRELLO", 
                         "Aggiunto prodotto ID $id_prodotto, quantità $quantita");
    }
    
    // Rimuovi dal carrello
    elseif (isset($_POST["azione"]) && $_POST["azione"] == "rimuovi") {
        $id_carrello = (int)$_POST["id_carrello"];
        $sql = "DELETE FROM TB_CARRELLO WHERE id_carrello = ? AND id_utente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id_carrello, $id_utente);
        $stmt->execute();
        $stmt->close();
        $message = "Prodotto rimosso dal carrello!";
        registraAttivita($conn, $id_utente, "RIMOZIONE_CARRELLO", "Rimosso prodotto ID $id_carrello");
    }
    
    // Svuota carrello
    elseif (isset($_POST["azione"]) && $_POST["azione"] == "svuota") {
        $sql = "DELETE FROM TB_CARRELLO WHERE id_utente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_utente);
        $stmt->execute();
        $stmt->close();
        $message = "Carrello svuotato!";
        registraAttivita($conn, $id_utente, "SVUOTA_CARRELLO", "Carrello completamente svuotato");
    }
    
    // Checkout (crea ordine)
    elseif (isset($_POST["azione"]) && $_POST["azione"] == "checkout") {
        $carrello = getCarrello($conn, $id_utente);
        
        if (count($carrello) == 0) {
            $error = "Carrello vuoto!";
        } else {
            // Calcola totale
            $totale = 0;
            foreach ($carrello as $item) {
                $totale += $item["prezzo"] * $item["quantita"];
            }
            
            // Crea ordine
            $sql_ordine = "INSERT INTO TB_ORDINI (id_utente, data_ordine, totale, stato) 
                           VALUES (?, NOW(), ?, 'in_elaborazione')";
            $stmt = $conn->prepare($sql_ordine);
            $stmt->bind_param("id", $id_utente, $totale);
            $stmt->execute();
            $id_ordine = $stmt->insert_id;
            $stmt->close();
            
            // Crea dettagli ordine
            foreach ($carrello as $item) {
                $sql_dettaglio = "INSERT INTO TB_DETTAGLI_ORDINE 
                                  (id_ordine, id_prodotto, quantita, prezzo_unitario) 
                                  VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql_dettaglio);
                $stmt->bind_param("iiid", $id_ordine, $item["id_prodotto"], 
                                 $item["quantita"], $item["prezzo"]);
                $stmt->execute();
                $stmt->close();
            }
            
            // Svuota carrello
            $sql_delete = "DELETE FROM TB_CARRELLO WHERE id_utente = ?";
            $stmt = $conn->prepare($sql_delete);
            $stmt->bind_param("i", $id_utente);
            $stmt->execute();
            $stmt->close();
            
            $message = "Ordine creato con successo! ID: $id_ordine";
            registraAttivita($conn, $id_utente, "CHECKOUT", 
                             "Creato ordine ID $id_ordine, totale €$totale");
        }
    }
}

$carrello = getCarrello($conn, $id_utente);
$prodotti = getProdotti($conn);

// Calcola totale carrello
$totale_carrello = 0;
foreach ($carrello as $item) {
    $totale_carrello += $item["prezzo"] * $item["quantita"];
}

$conn->close();
?>

<html>
<head>
    <link rel="stylesheet" href="style.css">
    <title>Acquisti - La tua area acquisti</title>

</head>
<body>
    <?php include "menu.php"; ?>
    
    <div class="acquisti-container">
        <div class="welcome-card">
            <h2>🛒 Area Acquisti</h2>
            <p>Benvenuto nella sezione acquisti. Aggiungi prodotti al carrello e completa il tuo ordine.</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="two-columns">
            <!-- Catalogo Prodotti -->
            <div class="catalogo">
                <h3>📦 Catalogo Prodotti</h3>
                <div class="prodotti-grid">
                    <?php foreach ($prodotti as $prodotto): ?>
                        <div class="prodotto-card">
                            <h4><?php echo htmlspecialchars($prodotto["nome"]); ?></h4>
                            <p><?php echo htmlspecialchars(substr($prodotto["descrizione"] ?? "", 0, 80)); ?></p>
                            <div class="prezzo">€ <?php echo number_format($prodotto["prezzo"], 2); ?></div>
                            <form method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="azione" value="aggiungi">
                                <input type="hidden" name="id_prodotto" value="<?php echo $prodotto["id_prodotto"]; ?>">
                                <input type="number" name="quantita" value="1" min="1" class="quantita-input">
                                <button type="submit" class="btn-aggiungi">➕ Aggiungi</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Carrello -->
            <div class="carrello-sidebar">
                <h3>🛍️ Il tuo Carrello</h3>
                
                <?php if (count($carrello) > 0): ?>
                    <?php foreach ($carrello as $item): ?>
                        <div class="carrello-item">
                            <div class="carrello-item-info">
                                <strong><?php echo htmlspecialchars($item["nome"]); ?></strong><br>
                                Quantità: <?php echo $item["quantita"]; ?><br>
                                <span class="carrello-item-prezzo">
                                    € <?php echo number_format($item["prezzo"] * $item["quantita"], 2); ?>
                                </span>
                            </div>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="azione" value="rimuovi">
                                <input type="hidden" name="id_carrello" value="<?php echo $item["id_carrello"]; ?>">
                                <button type="submit" class="btn-rimuovi">🗑️</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="totale">
                        Totale: € <?php echo number_format($totale_carrello, 2); ?>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="azione" value="svuota">
                        <button type="submit" class="btn-svuota">🗑️ Svuota Carrello</button>
                    </form>
                    
                    <form method="POST">
                        <input type="hidden" name="azione" value="checkout">
                        <button type="submit" class="btn-checkout">💰 Completa Acquisto</button>
                    </form>
                    
                <?php else: ?>
                    <div class="vuoto">
                        <span>🛒</span>
                        <p>Il tuo carrello è vuoto</p>
                        <small>Aggiungi prodotti dal catalogo</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>