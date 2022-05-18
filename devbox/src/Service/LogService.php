<?php

namespace App\Service;

use SplFileObject;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use App\Entity\Log;
use function PHPUnit\Framework\throwException;

class LogService
{
    private $em;
    private $projectDir;
    private $logCachePool;

    public function __construct(EntityManagerInterface $em, TagAwareCacheInterface $logCachePool, string $projectDir)
    {
        $this->em = $em;
        $this->logCachePool = $logCachePool;
        $this->projectDir = $projectDir;
    }

    private function checkArr($arr): bool
    {
        return is_array($arr) && count($arr) === 2;
    }

    public function parse(string $filename): bool
    {
        $em = $this->em;
        $logRepo = $em->getRepository(Log::class);
        $batchSize = 10;
        $ret = true;

        $filename = $this->projectDir . '/public/' . $filename;
        $file = new SplFileObject($filename);
        $file->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $currLine = 0;
        $maxLine = $logRepo->getMaxLine();
        if ($maxLine) {
            $currLine = $maxLine + 1;
        }

        try {
            while (!$file->eof()) {
                $file->seek($currLine);
                $line = $file->current();
                $lineParts = explode(' - - ', $line);
                $serviceName = $date = $statusCode = $timestamp = null;
                if ($this->checkArr($lineParts)) {
                    // get the service name
                    $serviceName = $lineParts[0];
                    $secondPart = $lineParts[1];

                    // get the date
                    preg_match('/\[(.*?)\]/s', $secondPart, $dateMatch);
                    if ($this->checkArr($dateMatch)) {
                        $date = $dateMatch[1];
                        $timestamp = strtotime($date);
                    }
                    // get the status code
                    preg_match('#(\d+)$#', $secondPart, $statusCodeMatch);
                    if ($this->checkArr($statusCodeMatch)) {
                        $statusCode = $statusCodeMatch[1];
                    }
                }

                // if everything's alright, save the log entries in batches of 10
                if ($serviceName && $date && $timestamp && $statusCode) {
                    $log = new Log();
                    $log->setName($serviceName);
                    $log->setTimestamp($timestamp);
                    $log->setStatus((int)$statusCode);
                    $log->setLine($currLine);
                    $em->persist($log);

                    if (($currLine % $batchSize) === 0) {
                        $em->flush();
                    }
                }

                $currLine++;
            }
        } catch (\Exception $exception) {
            $ret = false;
        }

        // clean up
        $em->flush();
        $em->clear();
        $file = null;

        return $ret;
    }

    public function getLogs($params): int
    {
        $qb = $this->em->createQueryBuilder('l');
        $qb->select('COUNT(l.id)')
            ->from('App\Entity\Log', 'l');
        $cacheKey = 'logs';

        if (!is_array($params)) {
            $params = [];
        }

        if (array_key_exists('serviceNames', $params)) {
            $serviceNames = explode(',', $params['serviceNames']);
            if (is_array($serviceNames)) {
                $cacheKey .= '_name_' . implode('_', $serviceNames);
                $qb->andWhere('l.name IN (:serviceNames)')
                    ->setParameter('serviceNames', $serviceNames);
            }
        }
        if (array_key_exists('startDate', $params)) {
            $startTime = strtotime($params['startDate']);
            $cacheKey .= '_start_' . $startTime;
            $qb->andWhere('l.timestamp > :startDate')
                ->setParameter('startDate', $startTime);
        }
        if (array_key_exists('endDate', $params)) {
            $endTime = strtotime($params['endDate']);
            $cacheKey .= '_end_' . $endTime;
            $qb->andWhere('l.timestamp < :endDate')
                ->setParameter('endDate', $endTime);
        }
        if (array_key_exists('statusCode', $params)) {
            $code = $params['statusCode'];
            $cacheKey .= '_code_' . $code;
            $qb->andWhere('l.status = :statusCode')
                ->setParameter('statusCode', (int)$code);
        }

        return $this->logCachePool->get($cacheKey, function (ItemInterface $item) use ($qb) {
            $item->expiresAfter(3600);

            return $qb->getQuery()->getSingleScalarResult();
        });
    }
}
