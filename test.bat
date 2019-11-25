@echo off

cls

rem php .\siac.php pad:convert "C:\Users\everton.INDEPENDENCIA\Desktop\siac.csv" "C:\Users\everton.INDEPENDENCIA\Documents\Prefeitura\2019\PAD\2019-10\pm\MES10" "C:\Users\everton.INDEPENDENCIA\Documents\Prefeitura\2019\PAD\2019-10\cm\MES10"
rem php .\siac.php pad:convert "C:\Users\everton.INDEPENDENCIA\Desktop\siac.db" "C:\Users\everton.INDEPENDENCIA\Documents\Prefeitura\2019\PAD\2019-10\pm\MES10" "C:\Users\everton.INDEPENDENCIA\Documents\Prefeitura\2019\PAD\2019-10\cm\MES10" --salvar
php .\siac.php pad:convert --carregar
