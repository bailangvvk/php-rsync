<?php

// 使用方法 请求 http://待备份的主机/sync_client.php?password=修改成你的密码&host=下发备份文件主机
$host = $_REQUEST['host'];
$remoteJsonUrl = 'http://' . $host . '/sync_server.php?password=修改成你的密码';

$logFile = 'sync_log.txt';
$lockFile = 'my_lock_file.lock';

/**
 * 递归扫描目录并获取文件信息
 * param string $dir 要扫描的目录路径
 * return array 文件信息数组
 */
function scanDirectory($directory)
{
	$currentPath = realpath(__DIR__);
	$allowedPaths = array(
		$currentPath,
	);

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	$files = array();

	foreach ($iterator as $file) {
		if ($file->isFile()) {
			$filePath = $file->getPathname();
			$realPath = realpath($filePath);

			if ($realPath !== false && isAllowedPath($realPath, $allowedPaths)) {
				$files[] = $realPath;
			}
		}
	}

	return $files;
}

function isAllowedPath($path, $allowedPaths)
{
	foreach ($allowedPaths as $allowedPath) {
		if (strpos($path, $allowedPath) === 0) {
			return true;
		}
	}

	return false;
}

/**
 * 获取远程 JSON 数据
 * param string $url 远程 JSON 数据 URL
 * return array|null 解析后的 JSON 数据数组或 null（如果请求失败）
 */
function getRemoteJson($url)
{
	$response = file_get_contents($url);
	return $response !== false ? json_decode($response, true) : null;
}

// function getRemoteJson($url)
// {
// 	$response = file_get_contents($url);
// 	if ($response !== false) {
// 		$remoteFiles = json_decode($response, true);
// 		$filteredFiles = array_filter($remoteFiles, function ($file) {
// 			return in_array($file['path'], ['ping.php', 'getskinids.php', 'getskinid.php', 'fireleaves.php']);
// 		});
// 		return array_values($filteredFiles);
// 	}
// 	return null;
// }

/**
 * 同步文件并记录日志
 * param array $remoteFiles 远程文件信息
 * param array $localFiles 本地文件信息
 * param string $logFile 日志文件路径
 */
function syncFiles($remoteFiles, $localFiles, $logFile)
{
	$remoteFileMap = array_column($remoteFiles, 'modified_time', 'path');
	$localFileMap = array_column($localFiles, 'modified_time', 'path');
	$logEntries = [];
	$hasChanges = false;

	$allowedPaths = array(
		// 添加白名单路径 sync_client.php为必须
		"example.txt"
	);

	// $allowedPaths = $_GET['allowedPaths'];


	foreach ($remoteFiles as $remoteFile) {
		$path = $remoteFile['path'];
		$remoteMTime = $remoteFile['modified_time'];

		foreach ($allowedPaths as $allowedPath) {
			if (strpos($path, $allowedPath) !== false) {
				if (!isset($localFileMap[$path])) {
					$logEntries[] = "新增文件: $path";
					downloadFile($path);
					$hasChanges = true;
				} elseif ($remoteMTime > $localFileMap[$path]) {
					$logEntries[] = "修改文件: $path";
					downloadFile($path);
					$hasChanges = true;
				}
			}
		}
	}

	foreach ($localFileMap as $path => $localMTime) {
		foreach ($allowedPaths as $allowedPath) {
			if (strpos($path, $allowedPath) !== false && !isset($remoteFileMap[$path])) {
				$logEntries[] = "删除文件: $path";
				unlink($path);
				$hasChanges = true;
			}
		}
	}

	if ($hasChanges) {
		$logEntries[] = "同步完成: " . date('Y-m-d H:i:s');
		file_put_contents($logFile, implode(PHP_EOL, $logEntries) . PHP_EOL, FILE_APPEND);
	}
}

/**
 * 从远程服务器下载文件
 * param string $filePath 要下载的文件路径
 */
function downloadFile($filePath)
{
	global $remoteJsonUrl;
	$url = $remoteJsonUrl . '&f=' . urlencode($filePath);
	$localPath = __DIR__ . DIRECTORY_SEPARATOR . $filePath;
	$localDir = dirname($localPath);

	if (!is_dir($localDir)) {
		mkdir($localDir, 0777, true);
	}

	file_put_contents($localPath, file_get_contents($url));
}

function preventConcurrentExecution($lockFile)
{
	global $remoteJsonUrl, $logFile; // 获取全局变量
	$fp = fopen($lockFile, 'c'); // 打开或创建锁文件

	if (flock($fp, LOCK_EX | LOCK_NB)) { // 尝试获得独占锁，非阻塞模式
		try {
			$remoteFiles = getRemoteJson($remoteJsonUrl); // 获取远程 JSON 数据

			// 漂亮格式化 JSON
			// $formattedJson = json_encode($remoteFiles, JSON_PRETTY_PRINT);
			// header('Content-Type: application/json');
			// echo $formattedJson;

			if ($remoteFiles === null) {
				die('无法获取远程 JSON 数据');
			}

			$localFiles = scanDirectory('.'); // 扫描本地目录并获取文件信息

			syncFiles($remoteFiles, $localFiles, $logFile); // 同步文件并记录日志

			// $formattedJson = json_encode($localFiles, JSON_PRETTY_PRINT);
			// header('Content-Type: application/json');
			// echo $formattedJson;


			echo "同步完成<br>";
			// flock($fp, LOCK_UN); // 释放锁
		} finally {
			flock($fp, LOCK_UN); // 释放锁
			// fclose($fp); // 关闭文件指针

		}
	} else {
		echo "同步中"; // 未能获得锁，返回提示信息
	}
	fclose($fp); // 关闭文件指针
	unlink($lockFile);
}

if (
	isset($_REQUEST['password'])
	&& isset($_REQUEST['host'])
	&& $_REQUEST['password'] = "20030216200302Aa+20030216200302Aa+"
	&& $_SERVER['REQUEST_METHOD'] === 'GET'
) {
	$startTime = microtime(true); // 记录开始时间
	preventConcurrentExecution($lockFile);
	$endTime = microtime(true); // 记录结束时间
	$runTime = $endTime - $startTime; // 计算运行时间
	echo "运行时间: " . $runTime . " 秒<br>"; // 打印运行时间
} else {
	http_response_code(404);
}

?>
