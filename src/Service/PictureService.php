<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PictureService
{
    private $params;

    // Constantes pour les formats d'image
    const FORMAT_PNG = 'image/png';
    const FORMAT_JPEG = 'image/jpeg';
    const FORMAT_WEBP = 'image/webp';

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * Ajoute une image, redimensionne et sauvegarde une version miniature.
     *
     * @param UploadedFile $picture Le fichier image à ajouter.
     * @param string|null $folder Le dossier de stockage (facultatif).
     * @param int|null $width La largeur de la miniature (facultatif).
     * @param int|null $height La hauteur de la miniature (facultatif).
     *
     * @return string Le nom du fichier généré.
     *
     * @throws \RuntimeException Si le répertoire n'existe pas ou si une erreur se produit lors de la manipulation de l'image.
     * @throws \InvalidArgumentException Si le format de l'image est incorrect.
     */
    public function add(UploadedFile $picture, ?string $folder = '', ?int $width = 250, ?int $height = 250): string
    {
        // Chemin vers le répertoire de stockage des images
        $path = $this->params->get('images_directory') . $folder;

        // Vérifie si le répertoire existe, sinon le crée
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true) && !is_dir($path)) {
                throw new \RuntimeException("Erreur lors de la création du dossier : $path");
            }
        }

        if (!is_dir($path . '/mini')) {
            if (!mkdir($path . '/mini', 0755, true) && !is_dir($path . '/mini')) {
                throw new \RuntimeException("Erreur lors de la création du dossier miniature : $path/mini");
            }
        }

        // Génère un nom unique pour l'image
        $fichier = md5(uniqid(rand(), true)) . '.webp';

        // Récupère les informations de l'image
        $picture_infos = getimagesize($picture->getPathname());

        if ($picture_infos === false) {
            throw new \InvalidArgumentException('Format d\'image incorrect');
        }

        // Vérifie le format de l'image et la charge
        switch ($picture_infos['mime']) {
            case self::FORMAT_PNG:
                $picture_source = imagecreatefrompng($picture->getPathname());
                break;
            case self::FORMAT_JPEG:
                $picture_source = imagecreatefromjpeg($picture->getPathname());
                break;
            case self::FORMAT_WEBP:
                $picture_source = imagecreatefromwebp($picture->getPathname());
                break;
            default:
                throw new \InvalidArgumentException('Format d\'image incorrect : ' . $picture_infos['mime']);
        }

        // Redimensionne l'image
        $imageWidth = $picture_infos[0];
        $imageHeight = $picture_infos[1];

        // Vérifie l'orientation et sélectionne la taille du carré
        switch ($imageWidth <=> $imageHeight) {
            case -1: // portrait
                $squareSize = $imageWidth;
                $src_x = 0;
                $src_y = ($imageHeight - $squareSize) / 2;
                break;
            case 0: // carré
                $squareSize = $imageWidth;
                $src_x = 0;
                $src_y = 0;
                break;
            case 1: // paysage
                $squareSize = $imageHeight;
                $src_x = ($imageWidth - $squareSize) / 2;
                $src_y = 0;
                break;
        }

        // Crée une nouvelle image redimensionnée
        $resized_picture = imagecreatetruecolor($width, $height);
        imagecopyresampled($resized_picture, $picture_source, 0, 0, $src_x, $src_y, $width, $height, $squareSize, $squareSize);

        // Sauvegarde l'image redimensionnée
        if (!imagewebp($resized_picture, $path . '/mini/' . $width . 'x' . $height . '-' . $fichier)) {
            throw new \RuntimeException("Impossible de sauvegarder l'image miniature dans : " . $path . '/mini/' . $width . 'x' . $height . '-' . $fichier);
        }

        // Déplace le fichier original
        try {
            $picture->move($path . '/', $fichier);
        } catch (\Exception $e) {
            throw new \RuntimeException("Impossible de déplacer le fichier téléchargé : " . $e->getMessage());
        }

        // Libération de la mémoire
        imagedestroy($picture_source);
        imagedestroy($resized_picture);

        return $fichier;
    }

    /**
     * Supprime une image et sa version miniature.
     *
     * @param string $fichier Le nom du fichier à supprimer.
     * @param string|null $folder Le dossier de stockage (facultatif).
     * @param int|null $width La largeur de la miniature (facultatif).
     * @param int|null $height La hauteur de la miniature (facultatif).
     *
     * @return bool True si l'image a été supprimée, False sinon.
     */
    public function delete(string $fichier, ?string $folder = '', ?int $width = 250, ?int $height = 250): bool
    {
        if ($fichier !== 'default.webp') {
            $success = false;
            $path = $this->params->get('images_directory') . $folder;

            // Supprime la miniature
            $mini = $path . '/mini/' . $width . 'x' . $height . '-' . $fichier;

            if (file_exists($mini)) {
                if (!unlink($mini)) {
                    throw new \RuntimeException("Impossible de supprimer le fichier miniature : $mini");
                }
                $success = true;
            }

            // Supprime l'image originale
            $original = $path . '/' . $fichier;

            if (file_exists($original)) {
                if (!unlink($original)) {
                    throw new \RuntimeException("Impossible de supprimer l'image originale : $original");
                }
                $success = true;
            }

            return $success;
        }

        return false;
    }
}
