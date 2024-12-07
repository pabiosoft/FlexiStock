<?php

namespace App\Command;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-category-slugs',
    description: 'Updates slugs for categories that don\'t have one'
)]
class UpdateCategorySlugsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        $updatedCount = 0;

        foreach ($categories as $category) {
            if (!$category->getSlug()) {
                $category->updateSlug();
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Updated slugs for %d categories.', $updatedCount));
        } else {
            $io->info('All categories already have slugs.');
        }

        return Command::SUCCESS;
    }
}
