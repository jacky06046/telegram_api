<?php

// 判斷是否有文件上傳
if (!isset($_FILES["file"])) {
  $result = array(
    "code" => 201,
    "msg" => "沒有上傳文件！"
  );
  outputResult($result);
  exit;
}

// 獲取上傳的文件名和擴展名
$file = $_FILES["file"]["name"];
$extension = pathinfo($file, PATHINFO_EXTENSION);

// 判斷上傳的文件類型是否允許
$fileType = $_FILES["file"]["type"];
$allowedTypes = ["image/gif", "image/jpeg", "image/jpg", "image/pjpeg", "image/x-png", "image/png"];
if (!in_array($fileType, $allowedTypes)) {
  $result = array(
    "code" => 201,
    "msg" => "只允許上傳gif、jpeg、jpg、png格式的圖片文件！"
  );
  outputResult($result);
  exit;
}

// 定義最大允許上傳的文件大小為5MB
$maxSize = 5 * 1024 * 1024;
$fileSize = $_FILES["file"]["size"];

// 如果上傳的文件大小超過最大允許大小，則進行壓縮
if ($fileSize > $maxSize) {
  $compressedImage = compress_image($_FILES["file"], $maxSize);
  if (!$compressedImage) {
    $result = array(
      "code" => 201,
      "msg" => "圖片壓縮失敗！"
    );
    outputResult($result);
    exit;
  }
  $fileType = $compressedImage['type'];
  $fileSize = $compressedImage['size'];
  $filepath = $compressedImage['tmp_name'];
} else {
  $filepath = $_FILES["file"]["tmp_name"];
}

// 調用upload_image函數上傳圖片
$imgpath = upload_image($filepath, $fileType, $file);
if ($imgpath) {
  $image_host = 'https://i'.rand(0, 3).'.wp.com/修改成你的反代域名';
  $result = array(
    "code" => 200,
    "msg" => "上傳成功",
    "url" => $image_host . $imgpath
  );
} else {
  $result = array(
    "code" => 201,
    "msg" => "圖片上傳失敗！請檢查接口可用性！"
  );
}

// 輸出結果
outputResult($result);

// 壓縮圖片函數
function compress_image($image, $maxSize) {
  if ($image['size'] <= $maxSize) {
    return $image;
  }

  $temp_file = tempnam(sys_get_temp_dir(), 'image');
  if (!$temp_file) {
    return false;
  }
  imagejpeg(imagecreatefromstring(file_get_contents($image['tmp_name'])), $temp_file, 80);
  $compressed_size = filesize($temp_file);

  if ($compressed_size <= $maxSize) {
    return array(
      'name' => $image['name'],
      'type' => 'image/jpeg',
      'tmp_name' => $temp_file,
      'error' => 0,
      'size' => $compressed_size
    );
  } else {
    unlink($temp_file);
    return false;
  }
}

// 輸出結果函數
function outputResult($result) {
  header("Content-type: application/json");
  echo json_encode($result, true);
}

// 上傳圖片函數
function upload_image($filepath, $fileType, $fileName) {
  $data = array(
    'file' => curl_file_create($filepath, $fileType, $fileName)
  );
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://telegra.ph/upload');
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $response = curl_exec($ch);
  curl_close($ch);

  $json = json_decode($response, true);
  if ($json && isset($json[0]['src'])) {
    return $json[0]['src'];
  } else {
    return false;
  }
}
