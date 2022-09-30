<?php
/**
 * ua-parser
 *
 * Copyright (c) 2011-2012 Dave Olsen, http://dmolsen.com
 *
 * Released under the MIT license
 */
namespace UAParser\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UAParser\Parser;

class ParserCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('ua-parser:parse')
            ->setDescription('Parses a user agent string and dumps the results.')
            ->addArgument(
                'user-agent',
                InputArgument::REQUIRED,
                'User agent string to analyze'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userAgent = $input->getArgument('user-agent');
        assert(is_string($userAgent));
        $result = Parser::create()->parse($userAgent);

        $output->writeln(json_encode($result, JSON_PRETTY_PRINT));

        return 0;
    }
}
