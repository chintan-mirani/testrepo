
%function excelServer
%_testpredbychintan1
global rootPath

config;

if findstr(pwd,'sverker')>0
    fprintf('Do not forget to start MAMP and to type in a terminal window: \nredis-server\n');
    %Put specific variables related to Sverker here
    fprintf('Setting variabeles specific to Sverkers machine\n');
    fid=fopen('mat_error.txt','a');
    download_plot_dir='';
    words_plot_dir='';
    %warning off
    mypath='/Users/sverkersikstrom/Dropbox/semantic/';
    addpath([mypath '']);
    addpath([mypath 'semanticCode']);
    addpath([mypath 'jsonlab']);
    dbstop in excelServer at 79
    %db_username='root@semanticexcel.com';
    %db_password='greencartoon123@!';%I guess it should be: mysql2Mariadb?
    %warning on
    print2console=1;
else
    
    javaaddpath('/home/matlab/matlabjarfiles/jdbc-driver.jar')

    rootPath = '/home/matlab/semanticmatlab';
    addpath('/home/wd/matlab/semanticmatlab');
    addpath('/home/matlab/semanticmatlab/semanticCode');
    addpath('/home/matlab/semanticmatlab/jsonlab');
    addpath('/home/matlab/semanticmatlab/redis-matlab/src');
    
    
    print2console=1;
    fid=fopen(matlab_error_log,'a');
end

%Set persistent/default parameters
setPar.excelServer=1;
setPar.persistent=1;
setPar.keywordsPlotPvalue=1;
setPar.plotBonferroni=0;
setPar.plotSignificantColors=6;%Colormap
setPar.Ncluster=4;
getPar(setPar);

x = 0;
command = '';
s.par.updateReportAutomatic=2;
t0=now;
db=1; %Use database
if db %Check that database connection works
    con=getDb(1);
    if length(con.Message)>0
        fprintf('Database error: %s\n',con.Message);
        stop
    end
end

R = redisConnection();
[Value, R, Status] = redisPing(R);
%If not PONG then put message that Redis is not working

while(true)
    [wdVal, R, Status] = redisLPop(R, 'wdQueue');
    disp('-----');
    disp(wdVal);
    disp('-----');
    if not(isempty(wdVal))
        wdData = loadjson(wdVal);
        wordset  = wdData.data;
        disp(wdData);
        
    end
    wdVal = false;
    pause(5)
    
end
fprintf('Matlab stops here, restarting in 1 s\n')
pause(1)

exit

