<?php
if(isset($_POST['Envoyer'])){
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] == 0) {
        $target_path= "filedir/";
        $target_path= $target_path . basename($_FILES['fichier']['name']);
        if(move_uploaded_file($_FILES['fichier']['tmp_name'],$target_path)){
            
            //initialiser les variables
            $status= ""; 
            $recup_id="";
            $response="";
             //Récupérer le nom de l'expéditeur
             $source_name= $_POST['source'];
            // Récupérer le message depuis le formulaire
            $message = $_POST['message'];
            //Récupérer les checkboxes
            $radio= $_POST['radiobox'];
            //recupérer la date
            $date_envoie= $_POST['date_envoie'];
            // Lire les numéros à partir du fichier TXT
            $numeros = file($target_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            //Compter le nbre de ligne du fichier = au nbre de numéro
            if($numeros !== false){
                $nbrerow=count($numeros);
            }
    
            //Récupération du nom du fichier
            $nom_fichier= $_FILES["fichier"]["name"];
                    
             

            if (count($numeros) > 0){
                try{
                    // Connexion à la base de données
                $servername="localhost";
                $conn = new PDO("mysql:host=$servername;dbname=projet_1", "root", "");

                }
                catch(Exception $e){
                    //en cas d'erreur, on affiche un message et on arrete tout
                    die("Erreur:".$e->getMessage());
                }
                
            
            
                // Boucle pour envoyer les SMS
                foreach ($numeros as $numero) {
                    
                    echo '<br>';
                     if($radio === "Non"){

                     
                    // Utilisez la méthode GET pour envoyer le SMS en utilisant l'API SMS
                    $api_url = "http://144.91.67.100:1401/send";
                    
                    $param= "?username=testuser2&password=testuser2";
                    $param= $param . "&to=" . urlencode($numero);
                    $param= $param . "&content=" . urlencode($message); 
                    $param= $param . "&from=" . urlencode($source_name);
                    
                    // Effectuez la requête HTTP GET
                    //$response = file_get_contents($api_url . $param);
                    $ch = curl_init($api_url . $param);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    $response = curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    curl_close($ch);
                    //var_dump($response);

                    //récupérer l'id de la reponse de l'API
                    $recup_id= explode('"', $response);
                    $recup_id=$recup_id[1];
                    
                    
                    
        
                    // Vérifiez la réponse de l'API 
                    if ($http_code === 200) {
                        // Réponse HTTP OK
                        $status = "Succes";                        
                        echo "Message envoyé au $numero.<br>";
                    } else {
                        // Erreur HTTP
                        $status = "Echec";
                        echo "Échec de l'envoi au $numero. Erreur HTTP : $http_code.";
                    }
                }else{
                    echo "Message planifié au $numero.<br>";
                }
                }

                //Insertion des données dans la table fichier 
                $insert=$conn->prepare("insert into fichier(nom_fichier,content_text,nbre_row,source_name,date_envoi,planifie) values(?,?,?,?,?,?)");
                $insert->execute([$nom_fichier,$message,$nbrerow,$source_name,$date_envoie,$radio]);

               // Récupération de l'id de la table fichier
                $fichier_id = $conn->lastInsertId();

                //var_dump($recup_id);
                // Insertion des données dans la table envoie_sms 
                $insert = $conn->prepare("INSERT INTO envoie_sms (num, sms_result, status, recup_id, fichier_id) VALUES (?, ?, ?, ?,?)");
                $insert->execute([$numero, $response, $status,$recup_id, $fichier_id]);

                
                
            
            
            }


        }
   
} else {
    echo "Une erreur s'est produite lors du chargement du fichier.";
}
   

}
?>
