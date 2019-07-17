<?php declare(strict_types=1);

namespace Loilo\XFilesystem\Test;

trait RepeatableTrait
{
    public function runBare(): void
    {
        $annotations = $this->getAnnotations();
        $pause = (int) ($annotations['method']['repeatPause'][0] ?? 0);
        $tries = (int) ($annotations['method']['repeatTries'][0] ?? 1);

        if ($tries <= 1) {
            parent::runBare();
            return;
        }

        for ($i = 0; $i < $tries; $i++) {
            if ($i > 0 && $pause > 0) {
                echo "Sleep for $pause milliseconds\n";
                usleep(1000 * $pause);
            }

            try {
                parent::runBare();
                return;
            } catch (\PHPUnit_Framework_IncompleteTestError $e) {
                throw $e;
            } catch (\PHPUnit_Framework_SkippedTestError $e) {
                throw $e;
            } catch (\Throwable $e) {
                var_dump($e->getMessage());
            } catch (\Exception $e) {
            }
        }

        if ($e) {
            throw $e;
        }
    }
}
