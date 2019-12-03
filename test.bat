@echo off

cls

rem php .\siac.php pad:convert "C:\Users\everton.INDEPENDENCIA\Desktop\siac.csv" "C:\Users\everton.INDEPENDENCIA\Documents\Prefeitura\2019\PAD\2019-10\pm\MES10" "C:\Users\everton.INDEPENDENCIA\Documents\Prefeitura\2019\PAD\2019-10\cm\MES10"
rem php .\siac.php pad:convert "C:\Users\everton.INDEPENDENCIA\Desktop\siac.db" "C:\Users\everton.INDEPENDENCIA\Documents\Prefeitura\2019\PAD\2019-10\pm\MES10" "C:\Users\everton.INDEPENDENCIA\Documents\Prefeitura\2019\PAD\2019-10\cm\MES10" --salvar
rem php .\siac.php pad:convert --carregar

rem php .\siac.php prog-orc:calc "C:\Users\everton.INDEPENDENCIA\Desktop\siac.db" "C:\Users\everton.INDEPENDENCIA\Desktop\progorc.pdf"

rem php .\siac.php pad:split "C:\Users\everton.INDEPENDENCIA\Desktop\siac.db" --saveTo="C:\Users\everton.INDEPENDENCIA\Documents\Prefeitura\2019\PAD\2019-10\"
rem php .\siac.php pad:split "C:\Users\everton.INDEPENDENCIA\Desktop\siac.db"

rem php .\siac.php vale-alim:calc "C:\Users\everton.INDEPENDENCIA\Desktop\pm.db"
rem php .\siac.php vale-alim:calc "C:\Users\everton.INDEPENDENCIA\Desktop\pm.db" --report="C:\Users\everton.INDEPENDENCIA\Desktop\vale.pdf"

rem php .\siac.php pessoal:dotacao "C:\Users\everton.INDEPENDENCIA\Desktop\pm.db"
rem php .\siac.php pessoal:dotacao "C:\Users\everton.INDEPENDENCIA\Desktop\pm.db" --report="C:\Users\everton.INDEPENDENCIA\Desktop\folha.pdf"

rem php .\siac.php contest:new-rule rteste
rem php .\siac.php contest:run "C:\Users\everton.INDEPENDENCIA\Desktop\siac.db" --rule=contas-patrimoniais --report="C:\Users\everton.INDEPENDENCIA\Desktop\contest.pdf"
rem php .\siac.php contest:new-profile mun
rem php .\siac.php contest:run "C:\Users\everton.INDEPENDENCIA\Desktop\pm.db" --profile=mun --report="C:\Users\everton.INDEPENDENCIA\Desktop\contest.pdf"