<?php

namespace App\Command;

use App\Entity\Photo;
use Cloudinary\Cloudinary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:images:optimize',
    description: 'Convertit les images existantes (WebP) ou les pousse sur le CDN, puis supprime les originaux lourds.'
)]
class OptimizeImagesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ?Cloudinary $cloudinary = null, // injection optionnelle
        private readonly string $imagesDir = ''
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche ce qui serait fait, sans rien écrire')
            ->addOption('mode', null, InputOption::VALUE_REQUIRED, 'local|cdn', 'local')
            ->addOption('max-width', null, InputOption::VALUE_REQUIRED, 'Largeur max lors de la conversion locale (px)', '1600')
            ->addOption('quality', null, InputOption::VALUE_REQUIRED, 'Qualité WebP (0-100) en mode local', '80')
            ->addOption('folder', null, InputOption::VALUE_REQUIRED, 'Dossier Cloudinary pour le mode cdn', 'snifandgo/uploads');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $dry     = (bool) $input->getOption('dry-run');
        $mode    = (string) $input->getOption('mode');
        $maxW    = (int) $input->getOption('max-width');
        $quality = (int) $input->getOption('quality');
        $folder  = rtrim((string) $input->getOption('folder'), '/');

        if (!is_dir($this->imagesDir)) {
            $io->error("Dossier images introuvable: {$this->imagesDir}");
            return Command::FAILURE;
        }

        $photos = $this->em->getRepository(Photo::class)->findAll();
        $count  = 0;
        foreach ($photos as $photo) {
            $name = $photo->getName();               // ex: "berger-abc123.jpg"
            if (!$name) {
                continue;
            }

            $srcPath = $this->imagesDir . '/' . $name;
            if (!is_file($srcPath)) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                continue; // on ignore le reste
            }

            if ($mode === 'cdn') {
                // ------------- MODE CDN -------------
                if ($photo->getCdnLink()) {
                    continue;
                } // déjà migrée

                $basename = pathinfo($name, PATHINFO_FILENAME);
                $publicId = $folder . '/' . $basename;

                $io->writeln("CDN ← {$name}  →  {$publicId}");

                if ($dry) {
                    $count++;
                    continue;
                }
                if (!$this->cloudinary) {
                    $io->error("Service Cloudinary non disponible. Vérifie services.yaml et CLOUDINARY_URL.");
                    return Command::FAILURE;
                }

                try {
                    $res = $this->cloudinary->uploadApi()->upload($srcPath, [
                        'public_id'     => $publicId,
                        'overwrite'     => false,
                        'resource_type' => 'image',
                    ]);
                    $photo->setCdnLink($res['public_id'] ?? $publicId);

                    // supprime la copie locale après succès
                    @unlink($srcPath);

                    $this->em->persist($photo);
                    $count++;
                } catch (\Throwable $e) {
                    $io->warning("Échec CDN pour {$name}: " . $e->getMessage());
                    continue;
                }
            } else {
                // ------------- MODE LOCAL (conversion -> webp) -------------
                if ($ext === 'webp') {
                    continue;
                } // déjà optimisée

                $basename = pathinfo($name, PATHINFO_FILENAME);
                $dstName  = $basename . '.webp';
                $dstPath  = $this->imagesDir . '/' . $dstName;

                $io->writeln("LOCAL: {$name}  →  {$dstName} (w<={$maxW}, q={$quality})");

                if ($dry) {
                    $count++;
                    continue;
                }

                // Essaye Imagick, sinon GD
                $done = false;
                // if (class_exists(\Imagick::class)) {
                //     try {
                //         $img = new \Imagick($srcPath);
                //         // redimensionne si plus large que maxW (garde le ratio)
                //         if ($img->getImageWidth() > $maxW) {
                //             $img->thumbnailImage($maxW, 0, true);
                //         }
                //         $img->setImageFormat('webp');
                //         $img->setImageCompressionQuality($quality);
                //         $img->writeImage($dstPath);
                //         $img->clear();
                //         $done = true;
                //     } catch (\Throwable $e) {
                //         $io->warning("Imagick ko sur {$name}: " . $e->getMessage());
                //     }
                // }
                if (!$done) {
                    // GD fallback (simple, sans alpha PNG complexe)
                    $src = ($ext === 'png') ? imagecreatefrompng($srcPath) : imagecreatefromjpeg($srcPath);
                    if ($src) {
                        $w = imagesx($src);
                        $h = imagesy($src);
                        if ($w > $maxW) {
                            $ratio = $maxW / $w;
                            $nw = $maxW;
                            $nh = (int) round($h * $ratio);
                            $dst = imagecreatetruecolor($nw, $nh);
                            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                            $ok  = imagewebp($dst, $dstPath, $quality);
                            imagedestroy($dst);
                        } else {
                            $ok  = imagewebp($src, $dstPath, $quality);
                        }
                        imagedestroy($src);
                        $done = (bool)($ok ?? false);
                    }
                }

                if (!$done || !is_file($dstPath)) {
                    $io->warning("Conversion WebP échouée: {$name}");
                    continue;
                }

                // remplace en base par le nouveau nom .webp
                $photo->setName($dstName);
                $this->em->persist($photo);

                // supprime l’original lourd
                @unlink($srcPath);

                $count++;
            }
        }

        if (!$dry) {
            $this->em->flush();
        }

        $io->success(($dry ? '[DRY-RUN] ' : '') . "Traitement terminé. {$count} images optimisées.");
        return Command::SUCCESS;
    }
}
