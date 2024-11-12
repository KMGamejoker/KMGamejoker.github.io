<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = getDatabaseConnection();

function getDatabaseConnection(){
    $dbHost = "localhost";
    $dbName = "Quotes";
    $dbUser = "QuotesUser";
    $dbPass = "y4dPZ49E62RS8nM";

    try{
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e){
        return [
            'error' => true,
            'message' => $e->getMessage()
        ];
    }
}

if (is_array($conn) && isset($conn['error'])) {
    die("Database connection error: " . $conn['message']);
}

$response = new stdClass();

$secondsInDay = 86400;
$CurrentTime = time();
$Tijd = 0;
$IDnum = 1;
$quote = "";

$stmt = $conn->prepare("SELECT * FROM Daily WHERE ID = :ID");
$stmt->bindparam(":ID",$IDnum);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result){
    $Tijd = $result['Tijd'];
} else {
    $quote = "Geen slot gevonden in Daily";
    exit;
}

$PassedTijd = $CurrentTime - $Tijd;
$TimeTillNewQuote = $secondsInDay - $PassedTijd;
$NewQuoteOver = 0;

if ($PassedTijd > $secondsInDay){
    while($PassedTijd > $secondsInDay){
        $PassedTijd -= $secondsInDay;
    }
    $stmt = $conn->prepare("SELECT * FROM Quotes");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($result){
        $rowCount = count($result);
        if ($rowCount > 0) {
            $ID = rand(1, $rowCount);

            $stmt = $conn->prepare("UPDATE Daily SET Tijd = :Tijd WHERE ID = :ID");
            $stmt->bindparam(":ID", $IDnum);
            $stmt->bindparam(":Tijd", $CurrentTime);
            $stmt->execute();

            $result = GetQuote($conn, $ID);
            if ($result) {
                $quote = $result['Quote'];
                $stmt = $conn->prepare("UPDATE Daily SET RandomID = :RandomID WHERE ID = :ID");
                $stmt->bindparam(":ID",$IDnum);
                $stmt->bindparam(":RandomID", $ID);
                $stmt->execute();
            } else {
                $quote = "Quote not found.";
            }
        } else {
            $quote = "No quotes available.";
        }
    } else {
        $quote = "No quotes result";
    }
}else {
    $NewQuoteOver = BerekenTijd($TimeTillNewQuote);
    $stmt = $conn->prepare("SELECT * FROM Daily WHERE ID = :ID");
    $stmt->bindparam(":ID",$IDnum);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $ID = $result['RandomID'];
    $result = GetQuote($conn, $ID);
    if ($result){
        $quote = $result["Quote"];
    }else{
        $quote = "Quote = null";
    }
}

$response->Quote = $quote;
$response->Wait = $NewQuoteOver;
die(json_encode($response));

function GetQuote($conn, $ID){
    $stmt = $conn->prepare("SELECT * FROM Quotes WHERE QuoteID = :QuoteID");
    $stmt->bindparam(":QuoteID", $ID);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}
function BerekenTijd($TimeTillNewQuote){
    $UUR = floor($TimeTillNewQuote / 3600);
    if ($UUR < 1){
       $Min = $TimeTillNewQuote / 60;
       $Tijd = "$Min"."Minuten";
       return $Tijd;
    }
    $Tijd = "$UUR"."Uur";
    return $Tijd;
}
?>