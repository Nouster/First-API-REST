<?php

namespace App\controller;

use App\crud\ActorsCrud;
use PDO;

class ActorsCrudController
{
    private const ACCEPTED_COLLECTION_METHODS = ["GET", "POST"];
    private const ACCEPTED_RESOURCE_METHODS = ["GET", "PUT", "DELETE"];
    private ActorsCrud $actorsCrud;
    public function __construct(
        private PDO $pdo,
        private string $uri,
        private string $httpMethod,
        private array $uriParts,
        private int $uriPartsCount
    ) {
        $this->actorsCrud = new ActorsCrud($pdo);

        if ($uri === "/actor" && !in_array($this->httpMethod, self::ACCEPTED_COLLECTION_METHODS)) {
            echo json_encode([
                'error' => 'Les méthodes accteptées pour les collections sont : ' . implode(" - ", self::ACCEPTED_COLLECTION_METHODS)
            ]);
            exit;
        }

        if (str_contains($uri, "/actor/") && !in_array($this->httpMethod, self::ACCEPTED_RESOURCE_METHODS)) {
            echo json_encode([
                'error' => 'Les méthodes accteptées pour les ressources uniques sont : ' . implode(" - ", self::ACCEPTED_RESOURCE_METHODS)
            ]);
            exit;
        }


        if ($uri === "/actor" && $httpMethod === "GET") {
            echo json_encode($this->actorsCrud->readAllActors());
            exit;
        }

        if ($uri === "/actor" && $httpMethod === "POST") {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['firstname_a']) || !isset($data['name_a']) || !isset($data['gender_a'])) {
                http_response_code(422);
                echo json_encode([
                    'error' => 'Firstname, name and gender or required'
                ]);
                exit;
            }
            json_encode($this->actorsCrud->createActor($data));
            $insertActorId = $pdo->lastInsertId();
            http_response_code(201);
            echo json_encode([
                'uri' => '/actor/' . $insertActorId
            ]);
            exit;
        }

        // unique
        $id = intval($uriParts[2]);
        if ($id === 0) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Acteur non trouvé'
            ]);
            exit;
        }

        // lire
        if ($uriPartsCount === 3 && $uriParts[1] === "actor" && $httpMethod === "GET") {
            $findActor = $this->actorsCrud->readOneActor($id);
            if ($findActor === false) {
                http_response_code(404);
                echo json_encode(['error' => "Acteur non trouvé"]);
                exit;
            }
            echo json_encode($findActor);
            http_response_code(200);
        }

        // modifier
        if ($uriPartsCount === 3 && $uriParts[1] === "actor" && $httpMethod === "PUT") {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['firstname_a']) || !isset($data['name_a']) || !isset($data['gender_a'])) {
                http_response_code(422);
                echo json_encode([
                    'error' => 'Le prénom, le nom et le genre sont requis'
                ]);
                exit;
            }
            $updatedActor = $this->actorsCrud->updateActor($data, $id);
            if ($updatedActor === false) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Acteur non trouvé'
                ]);
                exit;
            }
            http_response_code(204);
            exit;
        }

        // supprimer
        if ($uriPartsCount === 3 && $uriParts[1] === "actor" && $httpMethod === "DELETE") {
            $deletedActor = $this->actorsCrud->deleteActor($id);
            if ($deletedActor === false) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'Acteur non trouvé'
                ]);
                exit;
            }
            http_response_code(204);
        }
    }
}
