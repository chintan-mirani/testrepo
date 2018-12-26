
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
    javaaddpath([mypath 'matlabjarfiles/json_simple-1.1.jar'])
    javaaddpath([mypath 'matlabjarfiles/jedis-2.1.0.jar'])
    javaaddpath([mypath 'matlabjarfiles/final.jar'])
    dbstop in excelServer at 79
    %db_username='root@semanticexcel.com';
    %db_password='greencartoon123@!';%I guess it should be: mysql2Mariadb?
    %warning on
    print2console=1;
else
    javaaddpath('/home/semantic/matlabjarfiles/json_simple-1.1.jar')
    javaaddpath('/home/semantic/matlabjarfiles/jedis-2.1.0.jar')
    javaaddpath('/home/semantic/matlabjarfiles/final.jar')
    javaaddpath('/home/semantic/matlabjarfiles/jdbc-driver.jar')
    
    rootPath = '/home/semantic/semanticmatlab';
    addpath('/home/semantic/semanticmatlab');
    addpath('/home/semantic/semanticmatlab/semanticCode');
    addpath('/home/semantic/semanticmatlab/jsonlab');
    
    
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
meexcel = semantic.semanticExcelABunction();
command = '';
s.par.updateReportAutomatic=2;

count=0;
t0=now;
db=1; %Use database
if db %Check that database connection works
    con=getDb(1);
    if length(con.Message)>0
        fprintf('Database error: %s\n',con.Message);
        stop
    end
end
ErrorTime=0;
h=[];

% if isempty(fetch(getDb,'show tables like "spaceEnglish";'))
%     spaceToDb('spaceEnglish')
% end
% if isempty(fetch(getDb,'show tables like "spaceSwedish";'))
%     spaceToDb('spaceSwedish')
% end
while true
    try
        answer='';
        if abs(t0-now)*3600*24<.2
            if exist('debugCode.m')
                pause(2);fprintf('Running debugCode.m!\n')
                debugCode;
            end
            pause(0.2);
        else
            1;%No need to pause after running a command lasting for more then .2 seconds
        end
        t0=now;
        if even(count,5)
            fprintf('.');
        end
        tic;
        command = '';
        
        %getProperty command
        command = meexcel.getCommand();	%THIS ROW MAKES MY PROGRAM TO CRASH AFTER SOME 30 ITERATIONS! SVERKER
        singlemultiple = command.get('singlemultiple');
        if not(isempty(singlemultiple))
            document=command.get('documentid');
            refkey  = command.get('refkey');
            documentlanguage=command.get('documentlanguage');
        end
        
        try
            if isempty(singlemultiple) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                
                if strcmp(singlemultiple,'singletext')
                    w1 = command.get('word1');
                    w2 = command.get('word2');
                    ref1 = command.get('refword');
                    if db
                        %[s, index] =getSfromDB(initSpace(command),documentlanguage,document,{ref1},{w1},'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                        %[s, index2]=getSfromDB(s,documentlanguage,document,{w2},{w2},'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                        [s, index12] =getSfromDB(initSpace(command),documentlanguage,document,[{ref1} {w2}],[{w1} {w2}],'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                        index=index12(1:end-1);
                        index2=index12(end);                        
                    else
                        [s, ~, index]=setProperty(s,ref1,'_text',w1);
                    end
                    
                    [a b answer] = getProperty(s, index2, index);
                    answer=answer{1};
                    
                elseif strcmp(singlemultiple,'multipletext')
                    wordset1    = command.get('wordset1');
                    refwordset1    = command.get('refwordset1');
                    word2    = command.get('word2');
                    ref='';
                    currws=[];
                    
                    for j=1:wordset1.size,
                        currws{j} = wordset1.get(j-1);
                        text{j}='_text';
                        ref{j}=strcat('_ref',document,refwordset1.get(j-1));
                    end
                    if db
                        %[s, index]=getSfromDB(initSpace(command),documentlanguage,document,ref,    currws, 'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                        %[s, index2]=getSfromDB(s,                 documentlanguage,document,{word2},{word2},'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"                        
                        [s, index12]=getSfromDB(initSpace(command),documentlanguage,document,[ref {word2}],    [currws {word2}], 'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                        index=index12(1:end-1);
                        index2=index12(end);
                    else
                        [s tmpa index]=setProperty(s,ref,text,currws);
                    end
                    [a b answer1] = getProperty(s, index2, index);
                    
                    answer='';
                    for j=1:wordset1.size,
                        if j==1
                            answer = strcat(answer,answer1{j});
                        else
                            answer = strcat(answer,';',answer1{j});
                        end
                    end
                    
                elseif strcmp(singlemultiple,'properties')
                    word1    = command.get('word1');
                    word2    = command.get('word2');
                    if db
                        [s, index] =getSfromDB(initSpace(command),documentlanguage,document,{word1 word2},{word1 word2},'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                        %word1=index(1);word2=index(2);
                    end
                    [a b answer] = getProperty(s, index(1), index(2));
                    answer=answer{1};
                end
                m = java.util.HashMap;
                m.put('answer',answer);
                m.put('refkey',refkey);
                %display(m);
                meexcel.setCommand(m);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculation';
            m.put('answer',answer);
            m.put('refkey',refkey);
            meexcel.setCommand(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %getProperty command end
        
        %-----------------------------------------------
        %start setProperty API Function
        command = meexcel.getPropertyCommand();
        documentlanguage=command.get('documentlanguage');
        if not(isempty(documentlanguage))
            document = command.get('documentSpace');
            refkey  = command.get('refkey');
        end
        try
            if isempty(documentlanguage) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                rowdata = command.get('data');
                rowidentifier = command.get('identifier');
                rowdatalabel = command.get('datalabel');
                
                data = cell(rowdata);
                identifier = cell(rowidentifier);
                datalabel=cell(rowdatalabel);
                
                if db
                    [s, index] =getSfromDB(initSpace(command),documentlanguage,document,identifier,data,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s identifierOut index]=setProperty(s,identifier,datalabel,data);%Set property
                end
                
                if not(db) & isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    saveSpace(s,filename);%SS: It will be slow to save the space after every function.
                    %Condiser using this instead:
                    %%Save space in internal memory (not to harddrive).
                    %getSpace('set',s);
                    %And then load this space in loadSpace (s=getSpace in loadSpac)
                end
                
                m = java.util.HashMap;
                answer='saved successfully';
                m.put('answer',answer);
                m.put('refkey',refkey);
                %display(m);
                meexcel.setPropertyCommand(m);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('answer',answer);
            m.put('refkey',refkey);
            meexcel.setPropertyCommand(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', getReport(err));
        end
        %end setProperty function
        
        %---------------------------------------
        %start getProperty API Function
        command = meexcel.getGetPropertyCommand();
        rowidentifierOrText = command.get('identifierOrText');
        if not(isempty(rowidentifierOrText))
            document = command.get('documentSpace');
            refkey  = command.get('refkey');
            documentlanguage=command.get('documentlanguage');
        end
        try
            if isempty(rowidentifierOrText) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                parameterType =  command.get('parameterType');
                parameterValue =  command.get('parameterValue');
                
                for j=1:parameterType.size,
                    s.par.(parameterType.get(j-1)) = parameterValue.get(j-1);
                end
                
                property = command.get('property');
                ref=[];
                identifierOrText = {};
                
                for j=1:rowidentifierOrText.size,
                    identifierOrText{j} = rowidentifierOrText.get(j-1);
                    ref{j}=['_ref' num2str(j)];
                end
                if db
                    [s, index2] =getSfromDB(initSpace(command),documentlanguage,document,ref,identifierOrText,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                    if property(1)=='_' %Assume a function
                        [s, index1] =getSfromDB(initSpace(command),documentlanguage,document,{property},{property},'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                    else %Assume a text
                        [s, index1] =getSfromDB(initSpace(command),documentlanguage,document,{'_reftext'},{property},'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                    end
                    [number string stringOrNumber s]=getProperty(s,index1,index2);%getProperty
                else
                    [number string stringOrNumber s]=getProperty(s,property,identifierOrText);%getProperty
                    getSpace('set',s);
                end
                m = java.util.HashMap;
                answer=cell2string(stringOrNumber);
                m.put('answer',answer);
                m.put('refkey',refkey);
                %display(m);
                meexcel.setGetPropertyAPICommand(m);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('answer',answer);
            m.put('refkey',refkey);
            meexcel.setGetPropertyAPICommand(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', getReport(err));
        end
        
        %--------------------------------------------------------
        %semantictest command
        command = meexcel.getCommandSemantictest();
        wordset1    = command.get('wordset1');
        if not(isempty(wordset1))
            document=command.get('documentid');
            refkey  = command.get('refkey');
            documentlanguage=command.get('documentlanguage');
            wordset2    = command.get('wordset2');
            pairedsemantictest     = command.get('pairedsemantictest') ;
            refwordset1    = command.get('refwordset1');
            refwordset2    = command.get('refwordset2')  ;
            prefix      = command.get('prefix') ;
        end
        try
            if isempty(wordset1) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                selectioncriteria = command.get('selectioncriteria');
                selectioncell = command.get('selectioncell');
                
                setword1 = {};
                setword2 = {};
                refword1 = {};
                refword2 = {};
                textword1 = {};
                textword2 = {};
                setindex1 = [];
                setindex2 = [];
                
                for j=1:wordset1.size,
                    setword1{j} = wordset1.get(j-1);
                    refword1{j} = strcat(prefix,refwordset1.get(j-1));
                    textword1{j}='_text';
                end
                for j=1:wordset2.size,
                    setword2{j} = wordset2.get(j-1);
                    refword2{j} = strcat(prefix,refwordset2.get(j-1));
                    textword2{j}='_text';
                end
                
                if db
                    [s, setindex1] =getSfromDB(s,documentlanguage,document,refword1,setword1,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                    [s, setindex2] =getSfromDB(s,documentlanguage,document,refword2, setword2,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s newword setindex1]=setProperty(s,refword1,textword1,setword1);
                    [s newword setindex2]=setProperty(s,refword2,textword2,setword2);
                end
                if isempty(pairedsemantictest) == false && pairedsemantictest=='1'
                    s.par.match_paired_test_on_subject_property=1;
                else
                    s.par.match_paired_test_on_subject_property=0;
                end
                
                if isempty(selectioncriteria) == false
                    x1= {};
                    for j=1:selectioncell.size,
                        x1{j}=selectioncell.get(j-1);
                    end
                    x=str2double(x1);
                    formula=selectioncriteria;%Choice formula to select
                    eval(['selection=find(' formula ');'])
                    if s.par.match_paired_test_on_subject_property
                        group1=1:length(setindex1(selection));%User input, with a length that must macth the first set of indexes
                        group2=1:length(setindex2(selection));%User input, with a length that must macth the second set of indexes
                        [out,s]=semanticTest(s,setindex1(selection),setindex2(selection),'','',group1,group2);
                    else
                        [out,s]=semanticTest(s,setindex1(selection),setindex2(selection));
                    end
                else
                    if s.par.match_paired_test_on_subject_property
                        group1=1:length(setindex1);%User input, with a length that must macth the first set of indexes
                        group2=1:length(setindex2);%User input, with a length that must macth the second set of indexes
                        [out,s]=semanticTest(s,setindex1,setindex2,'','',group1,group2)
                    else
                        [out,s]=semanticTest(s,setindex1,setindex2);
                    end
                end
                
                if not(db) getSpace('set',s);end
                
                
                m = java.util.HashMap;
                answer=out.results;
                m.put('results',answer);
                m.put('refkey',refkey);
                meexcel.setSemantictest(m);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setSemantictest(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        
        %clusterspace function
        command = meexcel.getCommandClusterspace();
        wordset          = command.get('wordset');
        if not(isempty(wordset))
            document=command.get('documentid');
            documentlanguage=command.get('documentlanguage');
            refkey  = command.get('refkey');
            clusteramount    = command.get('amount');
            clustercategory  = command.get('clustername');
            refwordset       = command.get('refwordset');
            prefix           = command.get('prefix');
        end
        
        try
            if isempty(wordset) == false
                if db
                    s=initSpace(command);
                    s.par.db2space=1;
                    s.par.user=command.get('userIdentifier');%'USER IS A MISSING INPUT HERE'
                    s.filename=getSpaceName(documentlanguage);
                    s.languagefile=getSpaceName(documentlanguage);
                    
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                selectioncriteria = command.get('selectioncriteria');
                selectioncell = command.get('selectioncell');
                
                if isempty(clustercategory) == false
                    clustercategory = strcat('_',clustercategory);
                else
                    clustercategory = '';
                end
                clusteramount   = str2double(clusteramount);
                setword = {};
                refword = {};
                textword = {};
                myindex = [];
                
                for j=1:wordset.size,
                    setword{j} = wordset.get(j-1);
                    refword{j} = strcat(prefix,refwordset.get(j-1));
                    textword{j}='_text';
                end
                if db
                    [s, myindex] =getSfromDB(s,documentlanguage,document,refword,setword,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s newword myindex]=setProperty(s,refword,textword,setword);
                end
                if isempty(selectioncriteria) == false
                    x1= {};
                    for j=1:selectioncell.size,
                        x1{j}=selectioncell.get(j-1);
                    end
                    x=str2double(x1);
                    formula=selectioncriteria;%Choice formula to select
                    eval(['selection=find(' formula ');']);
                    [s info]=clusterSpace(s,myindex(selection),clusteramount,clustercategory);
                else
                    [s info]=clusterSpace(s,myindex,clusteramount,clustercategory);
                end
                %added for save
                if not(db)
                    getSpace('set',s);
                    saveSpace(s,filename)
                end
                
                m = java.util.HashMap;
                answer=info.text;
                m.put('results', answer);
                m.put('infoy', mat2str(info.y));
                m.put('refkey',refkey);
                meexcel.setClusterspace(m);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setClusterspace(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %end clusterspace function
        
        %similarity function
        command = meexcel.getCommandSimilarity();
        refkey  = command.get('refkey');
        if not(isempty(refkey))
            document=command.get('documentid');
            documentlanguage=command.get('documentlanguage');
            filename='';
            singlemultiple  = command.get('singlemultiple');
        end
        try
            if isempty(refkey) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                %norm text comes here in semanticNorm
                semanticNorm = command.get('semanticNorm');
                
                
                word=[];ref=[];
                if strcmp(singlemultiple,'singletext');
                    word{1}    = command.get('word1');
                    ref{1}    = command.get('ref1');
                    
                    if(isempty(semanticNorm))
                        word{2}    = command.get('word2');
                        ref{2}    = command.get('ref2');
                    else
                        word{2}    = fixpropertyname(semanticNorm);
                        ref{2}    = fixpropertyname(semanticNorm);
                    end
                    
                    %First I will save the word references
                    
                    if db
                        [s, index]=getSfromDB(initSpace(command),documentlanguage,document,ref,word,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                    else
                        [s tmp index(1)]=setProperty(s,ref{1},'_text',word{1});
                        [s tmp index(2)]=setProperty(s,ref{2},'_text',word{2});
                    end
                    
                    [~,~ , answer,s] = getProperty(s,index(1),index(2)); %similarity(ref1,ref2);
                    m = java.util.HashMap;
                    answer=answer{1};
                    m.put('results',answer);
                    m.put('refkey',refkey);
                    meexcel.setSimilarity(m);
                else
                    text1    = command.get('text1');
                    reftext1    = command.get('reftext1');
                    text2    = command.get('text2');
                    reftext2    = command.get('reftext2');
                    prefix    = command.get('prefix');
                    
                    setword1 = {};
                    refword1 = {};
                    setword2 = {};
                    refword2 = {};
                    textword1 = {};
                    textword2 = {};
                    
                    wordindexes = [];
                    for j=1:text1.size,
                        setword1{j} = text1.get(j-1);
                        refword1{j} = strcat(prefix,reftext1.get(j-1));
                        textword1{j}='_text';
                    end
                    
                    if(isempty(semanticNorm))
                        for j=1:text2.size,
                            setword2{j} = text2.get(j-1);
                            refword2{j} = strcat(prefix,reftext2.get(j-1));
                            textword2{j}='_text';
                        end
                    else
                        setword2{1} = fixpropertyname(semanticNorm);
                        refword2{1} = fixpropertyname(semanticNorm);
                        %refword2{1} = 'ref2';
                        textword2{1} = '_text';
                    end
                    
                    if db
                        [s, index1]=getSfromDB(initSpace(command),documentlanguage,document,refword1,setword1,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                        [s, index2]=getSfromDB(                 s,documentlanguage,document,refword2,setword2,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                    else
                        [s ,~ , index1]=setProperty(s,refword1,textword1,setword1);
                        [s ,~ , index2]=setProperty(s,refword2,textword2,setword2);
                    end
                    
                    if length(index2)==1
                        [~,~ , answer1,s]= getProperty(s,index1,index2);
                    else
                        for i=1:length(index1)
                            [~,~ , answer1(i),s]= getProperty(s,index1(i),index2(i));
                        end
                    end
                    answer='';
                    for i=1:length(index1)
                        if i==1
                            answer = strcat(answer,answer1{i});
                        else
                            answer = strcat(answer,';',answer1{i});
                        end
                    end
                    getSpace('set',s);
                    
                    m = java.util.HashMap;
                    m.put('results',answer);
                    m.put('refkey',refkey);
                    meexcel.setSimilarity(m);
                end
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setSimilarity(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %end similarity function
        
        %predict function
        command = meexcel.getCommandPredict();
        items    = command.get('items');
        try
            if isempty(items) == false
                document=command.get('documentid');
                documentlanguage=command.get('documentlanguage');
                if db
                    s=initSpace(command);
                    s.par.db2space=1;
                    s.par.user=command.get('userIdentifier');%'USER IS A MISSING INPUT HERE'
                    if isempty(s.par.user) s.par.user=0;end
                    s.filename=getSpaceName(documentlanguage);
                    s.languagefile=getSpaceName(documentlanguage);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                refkey  = command.get('refkey');
                assigned = command.get('assigned');
                cv    = command.get('cv');
                name     = command.get('name');
                activatetimeserie     = command.get('activatetimeserie');
                
                refitems    = command.get('refitems');
                refassigned    = command.get('refassigned')  ;
                numericaldata    = command.get('numericaldata') ;
                prefix      = command.get('prefix');
                selectioncriteria = command.get('selectioncriteria');
                selectioncell = command.get('selectioncell');
                covariates = command.get('covariatesdata');
                
                if isempty(activatetimeserie) == false && activatetimeserie=='1'
                    s.par.timeSerie=1;
                else
                    s.par.timeSerie=0;
                end
                setword = {};
                refword = {};
                textword = {};
                wordindexes = [];
                for j=1:items.size,
                    setword{j} = items.get(j-1);
                    refword{j} = strcat(prefix,refitems.get(j-1));
                    textword{j}='_text';
                end
                if db
                    [s, wordindexes] =getSfromDB(initSpace(command),documentlanguage,document,refword,setword,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s newword wordindexes]=setProperty(s,refword,textword,setword);
                end
                group = [];
                for j=1:cv.size,
                    group=[group,str2double(cv.get(j-1))];
                end
                trainData = [];
                for j=1:assigned.size,
                    trainData=[trainData,str2double(assigned.get(j-1))];
                end
                
                numericalDatas = [];
                if isempty(numericaldata) == false
                    for j=1:numericaldata.size,
                        numericaldata1=numericaldata.get(j-1);
                        for k=1:numericaldata1.size,
                            numericalDatas(j,k)=numericaldata1.get(k-1);
                        end
                    end
                end
                
                covariatesData = [];
                if isempty(covariates) == false
                    for j=1:covariates.size,
                        covariates1=covariates.get(j-1);
                        for k=1:covariates1.size,
                            covariatesData(j,k)=covariates1.get(k-1);
                        end
                    end
                end
                
                rng('default');
                if isempty(selectioncriteria) == false
                    x1= {};
                    for j=1:selectioncell.size,
                        x1{j}=selectioncell.get(j-1);
                    end
                    x=str2double(x1);
                    formula=selectioncriteria;%Choice formula to select
                    eval(['selection=find(' formula ');']);
                    [s info]=train(s,trainData(selection),name,wordindexes(selection),group(selection),numericalDatas(selection),covariatesData(selection));
                else
                    [s info]=train(s,trainData,name,wordindexes,group,numericalDatas,covariatesData);
                end
                if iscell(info)
                    info=info{end};
                end
                if db %Saving aldready done
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    getSpace('set',s);
                    saveSpace(s,filename,1);
                else
                    saveSpace(s,'spaceDemoScript');
                end
                m = java.util.HashMap;
                m.put('p',info.p);
                m.put('r',info.r);
                answer=info.results;
                m.put('results',info.results);
                m.put('refkey',refkey);
                meexcel.setPredict(m);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setPredict(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %end predict function
        
        %stdev function
        command = meexcel.getCommandStdev();
        items    = command.get('items');
        if not(isempty(items))
            document=command.get('documentid');
            documentlanguage=command.get('documentlanguage');
            refkey  = command.get('refkey');
            refitems    = command.get('refitems');
            prefix      = command.get('prefix');
            stdev='';
        end
        try
            if isempty(items) == false
                if db
                    s=initSpace(command);'NOT TESTED'
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                selectioncriteria = command.get('selectioncriteria');
                selectioncell = command.get('selectioncell');
                
                if isempty(selectioncriteria) == false
                    x=javaLinkedList2double(selectioncell);
                    formula=selectioncriteria;%Choice formula to select
                    eval(['selection=find(' formula ');']);
                    stdev=num2str(std(javaLinkedList2double(selection)),'%.2f');
                else
                    stdev=num2str(std(javaLinkedList2double),'%.2f');
                end
                m = java.util.HashMap;
                answer=stdev;
                m.put('results',answer);
                m.put('refkey',refkey);
                meexcel.setStdev(m);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setStdev(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %end stdev function
        
        %ttest function
        command = meexcel.getCommandTtest();
        items1 = command.get('items1');
        if not(isempty(items1))
            document=command.get('documentid');
            documentlanguage=command.get('documentlanguage');
            if db
                s=initSpace(command);
            elseif isempty(document) == false
                filename=strcat('documentSpaces/','space_document_',document,'.mat');
                s=loadSpace(filename,documentlanguage);
            end
            refkey = command.get('refkey') ;
            refitems1    = command.get('refitems1');
            items2    = command.get('items2');
            refitems2    = command.get('refitems2');
            tail  = command.get('tail');
            prefix      = command.get('prefix');
            results='';
        end
        try
            if isempty(items1) == false
                selectioncriteria = command.get('selectioncriteria');
                selectioncell = command.get('selectioncell');
                
                if isempty(selectioncriteria) == false
                    x=javaLinkedList2double(selectioncell);
                    formula=selectioncriteria;%Choice formula to select
                    eval(['selection=find(' formula ');']);
                    [h, p, ci, stats]=ttest2(javaLinkedList2double(selection),javaLinkedList2double(selection),.05,tail);
                else
                    [h, p, ci, stats]=ttest2(javaLinkedList2double(items1),javaLinkedList2double(items2),.05,tail);
                end
                results=sprintf('t(%d)=%.3f, p=%.4f\n',stats.df,stats.tstat,p);
                m = java.util.HashMap;
                answer=results;
                m.put('results',results);
                m.put('refkey',refkey);
                meexcel.setTtest(m);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setTtest(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %end ttest function
        
        %corr function
        command = meexcel.getCommandCorr();
        items1    = command.get('items1');
        if not(isempty(items1))
            document=command.get('documentid');
            documentlanguage=command.get('documentlanguage');
            refkey  = command.get('refkey')   ;
            refitems1    = command.get('refitems1');
            items2    = command.get('items2');
            refitems2    = command.get('refitems2');
            prefix      = command.get('prefix')	;
            results='';
        end
        try
            if isempty(items1) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                selectioncriteria = command.get('selectioncriteria');
                selectioncell = command.get('selectioncell');
                
                if isempty(selectioncriteria) == false
                    x=javaLinkedList2double(selectioncell);
                    formula=selectioncriteria;%Choice formula to select
                    eval(['selection=find(' formula ');']);
                    [r p]=nancorr((javaLinkedList2double(selection))',(javaLinkedList2double(selection))');
                else
                    %Use javaLinkedList2double to conver to numeric value:
                    [r p]=nancorr(javaLinkedList2double(items1)',javaLinkedList2double(items2)');
                end
                answer=sprintf('r=%.2f, p=%.4f\n',r,p);
                m = java.util.HashMap;
                m.put('results',answer);
                m.put('refkey',refkey);
                meexcel.setCorr(m);
            end
            
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setCorr(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %end corr function
        
        %wordstest function
        command = meexcel.getCommandKeywordstest();
        wordset1    = command.get('wordset1');
        if not(isempty(wordset1))
            document=command.get('documentid');
            documentlanguage=command.get('documentlanguage');
            refkey  = command.get('refkey');
            
            wordset2    = command.get('wordset2');
            refwordset1    = command.get('refwordset1');
            refwordset2    = command.get('refwordset2') ;
            
            prefix      = command.get('prefix')  ;
            correction  = command.get('correction') ;
        end
        try
            if isempty(wordset1) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                selectioncriteria = command.get('selectioncriteria');
                selectioncell = command.get('selectioncell');
                
                
                if isequal(correction,'NONE')
                    s.par.keywordCorrectionType=2;%=2 not corrected
                elseif isequal(correction,'HOLMES')
                    s.par.keywordCorrectionType=1;%=1 Holmes correction,
                elseif isequal(correction,'BONFERRONI')
                    s.par.keywordCorrectionType=0;%=0 Bonferroni correction,
                end
                setword1 = {};
                setword2 = {};
                refword1 = {};
                refword2 = {};
                textword1 = {};
                textword2 = {};
                setindex1 = [];
                setindex2 = [];
                for j=1:wordset1.size,
                    setword1{j} = wordset1.get(j-1);
                    refword1{j} = strcat(prefix,refwordset1.get(j-1));
                    textword1{j}='_text';
                end
                if db
                    [s, setindex1] =getSfromDB(initSpace(command),documentlanguage,document,refword1,setword1,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s newword setindex1]=setProperty(s,refword1,textword1,refword1);
                end
                if wordset2.size == 0
                    setindex2=NaN;
                else
                    for j=1:wordset2.size,
                        setword2{j} = wordset2.get(j-1);
                        refword2{j} = strcat(prefix,refwordset2.get(j-1));
                        textword2{j}='_text';
                    end
                    if db
                        [s, setindex2] =getSfromDB(initSpace(command),documentlanguage,document,refword2,setword2,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                    else
                        [s newword setindex2]=setProperty(s,refword2,textword2,setword2);
                    end
                end
                
                if isempty(selectioncriteria) == false
                    x1= {};
                    for j=1:selectioncell.size,
                        x1{j}=selectioncell.get(j-1);
                    end
                    x=str2double(x1);
                    formula=selectioncriteria;%Choice formula to select
                    eval(['selection=find(' formula ');']);
                    [out s]=keywordsTest(s,setindex1(selection),setindex2(selection));
                else
                    [out s]=keywordsTest(s,setindex1,setindex2);
                end
                m = java.util.HashMap;
                answer=out.results;
                m.put('results',answer);
                m.put('refkey',refkey);
                meexcel.setKeywordstest(m);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setKeywordstest(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %wordstest function
        
        %semantictestproperty function
        command = meexcel.getCommandSemantictestpropertymany();
        datas1    = command.get('datas1');
        if not(isempty(datas1))
            clear('data');
            document=command.get('documentid');
            documentlanguage=command.get('documentlanguage');
            refkey  = command.get('refkey');
            
            properties1    = command.get('properties1');
            refdatas1    = command.get('refdatas1');
            labels1    = command.get('labels1');
            datas2    = command.get('datas2');
            properties2    = command.get('properties2');
            refdatas2    = command.get('refdatas2');
            labels2    = command.get('labels2');
            
            prefix      = command.get('prefix');
        end
        try
            if isempty(datas1) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                selectioncriteria = command.get('selectioncriteria');
                selectioncell = command.get('selectioncell');
                
                setword1 = {};
                refword1 = {};
                textword1 = {};
                setindex1 = [];
                for j=1:datas1.size,
                    setword1{j} = datas1.get(j-1);
                    refword1{j} = strcat(prefix,refdatas1.get(j-1));
                    textword1{j}='_text';
                end
                
                if db
                    [s, setindex1]=getSfromDB(initSpace(command),documentlanguage,document,refword1,setword1,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s newword setindex1]=setProperty(s,refword1,textword1,setword1);
                end
                if isempty(selectioncriteria) == false
                    x1= {};
                    for j=1:selectioncell.size,
                        x1{j}=selectioncell.get(j-1);
                    end
                    x=str2double(x1);
                    formula=selectioncriteria;%Choice formula to select
                    eval(['selection=find(' formula ');']);
                    data{1}=setindex1(selection);
                else
                    data{1}=setindex1;
                end
                setword2 = {};
                refword2 = {};
                textword2 = {};
                setindex1 = [];
                for j=1:datas2.size,
                    setword2{j} = datas2.get(j-1);
                    refword2{j} = strcat(prefix,refdatas2.get(j-1));
                    textword2{j}='_text';
                end
                if db
                    [s, setindex1]=getSfromDB(initSpace(command),documentlanguage,document,refword2,setword2,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s newword setindex1]=setProperty(s,refword2,textword2,setword2);
                end
                if isempty(selectioncriteria) == false
                    x1= {};
                    for j=1:selectioncell.size,
                        x1{j}=selectioncell.get(j-1);
                    end
                    
                    x=str2double(x1);
                    formula=selectioncriteria;%Choice formula to select
                    eval(['selection=find(' formula ');']);
                    data{2}=setindex1(selection);
                else
                    data{2}=setindex1;
                end
                setproperties = {};
                setlabels = {};
                setproperties{1} = properties1;
                setproperties{2} = properties2;
                setlabels{1} = labels1;
                setlabels{2} = labels2;
                for k=3:20,
                    dataset=command.get(strcat('datas',int2str(k)));
                    refdataset=command.get(strcat('refdatas',int2str(k)));
                    if isempty(dataset) == false
                        setword = {};
                        refword = {};
                        textword = {};
                        setindex = [];
                        for j=1:dataset.size,
                            setword{j} = dataset.get(j-1);
                            refword{j} = strcat(prefix,refdataset.get(j-1));
                            textword{j}='_text';
                        end
                        if db
                            [s, setindex1]=getSfromDB(initSpace(command),documentlanguage,document,refword,refword,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                        else
                            [s newword setindex1]=setProperty(s,refword,textword,refword);
                        end
                        if isempty(selectioncriteria) == false
                            x1= {};
                            for j=1:selectioncell.size,
                                x1{j}=selectioncell.get(j-1);
                            end
                            x=str2double(x1);
                            formula=selectioncriteria;%Choice formula to select
                            eval(['selection=find(' formula ');']);
                            data{k}=setindex1(selection);
                        else
                            data{k}=setindex1;
                        end
                        
                        setproperties{k} = command.get(strcat('properties',int2str(k)));
                        setlabels{k} = command.get(strcat('labels',int2str(k)));
                    else
                        break;
                    end
                end
                property=setproperties;label=setlabels;
                datas2=data;property2=property;label2=label;
                [answer out]=semanticTestPropertyMany(s,data,property,label,datas2,property2,label2);
                
                if not(db) getSpace('set',s);end
                
                m = java.util.HashMap;
                m.put('results',answer);
                m.put('refkey',refkey);
                meexcel.setSemantictestpropertymany(m);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setSemantictestpropertymany(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %end semantictestproperty function
        
        %plotspace function
        command = meexcel.getCommandPlotspace();
        wordset  = command.get('wordset');
        
        if not(isempty(wordset))
            document=command.get('documentid');
            documentlanguage=command.get('documentlanguage');
            refkey  = command.get('refkey');
            
            xaxel    = command.get('xaxel');
            yaxel  = command.get('yaxel');
            
            refwordset  = command.get('refwordset');
            prefix  = command.get('prefix');
        end
        
        try
            if isempty(wordset) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                selectioncriteria = command.get('selectioncriteria');
                selectioncell = command.get('selectioncell');
                
                setword = {};
                refword = {};
                textword = {};
                for j=1:wordset.size,
                    setword{j} = wordset.get(j-1);
                    refword{j} = strcat(prefix,refwordset.get(j-1));
                    textword{j}='_text';
                end
                if db
                    [s, index] =getSfromDB(initSpace(command),documentlanguage,document,refword,setword,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s tmp index]=setProperty(s,refword,textword,setword);
                end
                
                data{1}=index;
                
                if db
                    [s, indexAxel] =getSfromDB(initSpace(command),documentlanguage,document,{'_reference1','_reference2'},{xaxel,yaxel},'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s tmp indexAxel(1)]=setProperty(s,'_reference1','_text',xaxel);
                    [s tmp indexAxel(2)]=setProperty(s,'_reference2','_text',yaxel);
                end
                if isempty(selectioncriteria) == false
                    x1= {};
                    for j=1:selectioncell.size,
                        x1{j}=selectioncell.get(j-1);
                    end
                    x=str2double(x1);
                    formula=selectioncriteria;%Choice formula to select
                    eval(['selection=find(' formula ');']);
                    if isempty(yaxel) == false
                        [h s]=plotSpace(s,data(selection),indexAxel(1),indexAxel(2));
                    else
                        [h s]=plotSpace(s,data(selection),indexAxel(1));
                    end
                else
                    if isempty(yaxel) == false
                        [h s]=plotSpace(s,data,indexAxel(1),indexAxel(2));
                    else
                        [h s]=plotSpace(s,data,indexAxel(1));
                    end
                end
                saveas(h,strcat(download_plot_dir,refkey,'.png'));%Saves the figure to an .eps file!
                m = java.util.HashMap;
                answer=strcat(download_plot_url,refkey,'.png');
                m.put('results', answer);
                m.put('refkey',refkey);
                meexcel.setPlotspace(m);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setPlotspace(m);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %end plotspace function
        
        %plotwordcount function
        command = meexcel.getCommandPlotWordcount()	;
        wordset  = command.get('wordset');
        
        if not(isempty(wordset))
            singlemultiple = command.get('singlemultiple') ;
            refkey  = command.get('refkey')  ;
            refwordset       = command.get('refwordset');
            prefix           = command.get('prefix');
            document=command.get('documentid');
            documentlanguage=command.get('documentlanguage');
            
            errormessage='';
        end
        try
            if isempty(wordset) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                selectioncriteria = command.get('selectioncriteria');
                selectioncell = command.get('selectioncell');
                plotwordparam = command.get('keywordsplot');
                figuretypeparam = command.get('figuretype');
                figuretype = 1:7; %set defaualt
                if isempty(figuretypeparam) == false
                    figuretype = loadjson(figuretypeparam);
                end
                
                s.par.figureType=figuretype;
                
                s.par.plotOnSemanticScale=0;
                
                if plotwordparam == '1'
                    s.par.plotwordCountCorrelation=0;
                elseif plotwordparam == '2'
                    s.par.plotwordCountCorrelation=1;
                elseif plotwordparam == '3'
                    s.par.plotOnSemanticScale=1;
                    s.par.plotwordCountCorrelation=0;
                end
                setword={};
                refword={};
                textword={};
                index=[];
                for j=1:wordset.size,
                    setword{j} = wordset.get(j-1);
                    refword{j} = strcat(prefix,refwordset.get(j-1));
                    textword{j}='_text';
                end
                if db
                    [s, index] =getSfromDB(initSpace(command),documentlanguage,document,refword,setword,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s tmp index]=setProperty(s,refword,textword,setword);
                end
                if strcmp(singlemultiple,'SINGLEVALUE')
                    axes={strcat('_',lower(command.get('instancex'))), strcat('_',lower(command.get('instancey')))};
                    if db
                        [s, indexAxel] =getSfromDB(initSpace(command),documentlanguage,document,{'_reference1','_reference2'},axes,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                    else
                        [s tmp indexAxel(1)]=setProperty(s,'_reference1','_text',axes{1});
                        [s tmp indexAxel(2)]=setProperty(s,'_reference2','_text',axes{2});
                    end
                    %[wordd1 s]=getWord(s,[],strcat('_',lower(command.get('instancex'))));
                    %[wordd2 s]=getWord(s,[],strcat('_',lower(command.get('instancey'))));
                    
                    if isempty(selectioncriteria) == false
                        x1= {};
                        for j=1:selectioncell.size,
                            x1{j}=selectioncell.get(j-1);
                        end
                        x=str2double(x1);
                        formula=selectioncriteria;%Choice formula to select
                        eval(['selection=find(' formula ');']);
                        if isempty(command.get('instancey')) == false
                            [s h out1]=plotWordcount(s,index(selection),indexAxel(1),indexAxel(2)); %wordset1=words to plot, wordd1=propery defining median split on x-axel, wordd2=propery defining median split on y-axel,
                        else
                            [s h out1]=plotWordcount(s,index(selection),indexAxel(1));%wordd1.index); %wordset1=words to plot, wordd1=propery defining median split on x-axel, wordd2=propery defining median split on y-axel,
                        end
                    else
                        if isempty(command.get('instancey')) == false
                            [s h out1]=plotWordcount(s,index,indexAxel(1),indexAxel(2)) ;%wordset1=words to plot, wordd1=propery defining median split on x-axel, wordd2=propery defining median split on y-axel,
                        else
                            [s h out1]=plotWordcount(s,index,indexAxel(1)); %wordset1=words to plot, wordd1=propery defining median split on x-axel, wordd2=propery defining median split on y-axel,
                        end
                    end
                elseif strcmp(singlemultiple,'MULTIPLEVALUE')
                    %plotWordCount can also be called with the xdata and ydata as input (given
                    %that either one of them has a lenght longer than 1)
                    xaxel  = command.get('xaxel');
                    yaxel  = command.get('yaxel');
                    xdata = {};
                    ydata = {};
                    for j=1:xaxel.size,
                        xdata{j}=str2double(xaxel.get(j-1));
                    end
                    xdata=cell2mat(xdata);
                    if isempty(yaxel) == false
                        for j=1:yaxel.size,
                            ydata{j}=str2double(yaxel.get(j-1));
                        end
                        ydata=cell2mat(ydata);
                    end
                    if isempty(selectioncriteria) == false
                        x1= {};
                        for j=1:selectioncell.size,
                            x1{j}=selectioncell.get(j-1);
                        end
                        x=str2double(x1);
                        formula=selectioncriteria;%Choice formula to select
                        eval(['selection=find(' formula ');']);
                        if isempty(yaxel) == false
                            [s h out1 out2]=plotWordcount(s,index(selection),xdata(selection),ydata(selection));
                        else
                            [s h out1]=plotWordcount(s,index(selection),xdata(selection));
                        end
                    else
                        if isempty(yaxel) == false
                            [s h out1 out2]=plotWordcount(s,index,xdata,ydata);
                        else
                            [s h out1]=plotWordcount(s,index,xdata);
                        end
                    end
                end
                
                for i=1:length(h)
                    saveas(h(i),strcat(download_plot_dir,refkey,num2str(i),'.png'));%Saves the figure to an .eps file!
                    saveas(h(i),strcat(download_plot_dir,refkey,num2str(i),'.fig'));
                end
                
                plotUrlStr = '';
                for j=1:length(h)-1
                    plotUrlStr = strcat(plotUrlStr, download_plot_url,refkey,num2str(j),'.png|');
                end
                plotUrlStr = strcat(plotUrlStr, download_plot_url,refkey,num2str(j+1),'.png');
                
                plotText = '';
                for j=1:length(h)-1
                    plotText = strcat(plotText, out1.figureText{h(j)},'|');
                end
                plotText = strcat(plotText, out1.figureText{h(j+1)});
                
                
                m = java.util.HashMap;
                results = out1.results;
                if isempty(yaxel) == false
                    results = strcat(results,'|',out2.results);
                end
                
                answer = strcat(plotUrlStr,'||',results,'||',plotText);
                m.put('results',answer);
                m.put('refkey',refkey);
                meexcel.setPlotWordcount(m);
            end
        catch err
            m = java.util.HashMap;
            if isempty(errormessage) == false
                answer=strcat('Error: ',errormessage,' unknown word');
                m.put('results',answer);
            else
                answer='Error during calculating';
                m.put('results',answer);
            end
            m.put('refkey',refkey);
            meexcel.setPlotWordcount(m);
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        
        %end plotwordcount function
        
        %plotwordcountcategory function
        command = meexcel.getPlotWordcountCategory();
        c = command.get('category');
        refkey = command.get('refkey');
        try
            if isempty(c) == false
                document=command.get('documentid');
                documentlanguage=command.get('documentlanguage');
                if db
                    s=initSpace(command);
                    s.languagefile=getSpaceName(documentlanguage);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                
                [id, categories, index,user,comments]=getIndexCategory(str2double(c),s,1);
                
                id=id(1:min(2000,length(id)));
                str='';
                for j=1:length(id)
                    if j==1
                        str=strcat(str,id{j});
                    else
                        str=strcat(str,'|',id{j});
                    end
                end
                m = java.util.HashMap;
                answer=str;
                m.put('results',answer);
                meexcel.setPlotWordcountInstances(m,refkey);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setPlotWordcountInstances(m,refkey);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %end plotwordcountcategory function
        
        %getParams function
        command = meexcel.getGetParams();
        refkey = command.get('refkey');
        try
            if isempty(refkey) == false
                str='';
                document=command.get('documentid');
                documentlanguage=command.get('documentlanguage');
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                opt.Compact=1;
                [temp1 temp2] = getPar;
                info1.field=temp2.field;
                info1.comment = temp2.comment;
                info1.options = temp2.optionsLong;
                info1.category = temp2.category;
                temp.info = info1;
                temp.result = temp1;
                answer=savejson('',temp,opt);
                m = java.util.HashMap;
                m.put('results', answer);
                meexcel.setGetParams(m,refkey);
            end
        catch err
            m = java.util.HashMap;
            answer='{msg: Error during calculating}';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setGetParams(m,refkey);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %getParams end function
        
        %3woords function
        command = meexcel.getCommand3wordsNew();
        wordset  = command.get('data');
        if not(isempty(wordset))
            refwordset = command.get('identifier');
            refkey  = command.get('refkey');
            valence = command.get('valence');
            document=command.get('documentSpace');
            documentlanguage=command.get('documentlanguage');
            errormessage='';
            figureNote = '';
        end
        try
            if isempty(wordset) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                
                compareData  = command.get('compareData');
                compareIde  = command.get('compareIde');
                plottype=command.get('plotType');
                plotCloudType=command.get('plotCloudType');
                plotCluster=command.get('plotCluster');
                plotWordcloud=command.get('plotWordcloud');
                plotTestType=command.get('plotTestType');%Remove?
                %fprintf('%s\n',plotTestType);%Remove? 
                userIdeNames=command.get('userIdeNames');
                userIdentifier=command.get('userIdentifier');
                numbersParam=command.get('numbersData');
                xaxel=command.get('xaxel');
                yaxel=command.get('yaxel');
                zaxel=command.get('zaxel');
                justTakenSurvey = command.get('justTakenSurvey');
                
                
                plotNominalLabels=command.get('plotNominalLabels');
                for i=1:plotNominalLabels.size
                    s.par.plotNominalLabels{i}=plotNominalLabels.get(i-1);
                end
                s.par.plotWordcloudType=command.get('plotWordcloudType');
                
                setword={};
                refword={};
                textword={};
                index=[];
                for j=1:wordset.size,
                    setword{j} = wordset.get(j-1);
                    refword{j} = refwordset.get(j-1);
                    textword{j}='_text';
                end
                if db
                    [s, index] =getSfromDB(s,documentlanguage,document,refword,setword,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s tmp index]=setProperty(s,refword,textword,setword);
                end
                
                userCallId=[];
                advanceParamJson=command.get('advanceParam');
                advanceParam=[];
                if isempty(advanceParamJson) == false & not(strcmpi(advanceParamJson,'[]')) %Never call with [], use '' instead!
                    advanceParam = loadjson(advanceParamJson);
                    if isfield(advanceParam,'plotProperty3') %Do NOT use plotProperty3, use plotProperty instead.
                        advanceParam.plotProperty=advanceParam.plotProperty3;
                    end
                    
                    s.par=structCopy(s.par,advanceParam);
                    
                    
                    %These 4 lines are not needed
                    if isfield(advanceParam, 'plotColorMap') s.par.plotColorMap=advanceParam.plotColorMap; end;
                    if isfield(advanceParam, 'plotFontname') s.par.plotFontname=advanceParam.plotFontname; end;
                    if isfield(advanceParam, 'plotBackGroundColor') s.par.plotBackGroundColor=advanceParam.plotBackGroundColor; end;
                    if isfield(advanceParam, 'userCallId') userCallId=advanceParam.userCallId; end;
                    if isfield(advanceParam, 'diagnosUserId')
                        userCallId=advanceParam.diagnosUserId;
                        fprintf('Setting userCallId to: %s\n',userCallId)
                    end;
                    
                end;
                
                setword1={};
                refword1={};
                textword1={};
                %index1=[];
                for j=1:compareData.size,
                    setword1{j} = compareData.get(j-1);
                    refword1{j} = compareIde.get(j-1);
                    textword1{j}='_text';
                end
                
                userIdes={};
                userIdNames={};
                for j=1:userIdentifier.size,
                    userIdes{j} = userIdentifier.get(j-1);
                    userIdNames{j} = userIdeNames.get(j-1);
                end
                
                %number calculation
                numbers = []; %default single dimension
                xdata = {};
                ydata = {};
                zdata = {};
                if isempty(xaxel) == false
                    for j=1:xaxel.size,
                        xdata{j}=str2double(xaxel.get(j-1));
                    end
                end
                xdata=cell2mat(xdata);
                indexNaN=find(isnan(xdata));
                if length(indexNaN)>0 fprintf('Warning: Missing xdata on %d datapoints\n',length(indexNaN)); end
                
                if isempty(yaxel) == false
                    for j=1:yaxel.size,
                        ydata{j}=str2double(yaxel.get(j-1));
                    end
                end
                ydata=cell2mat(ydata);
                indexNaN=find(isnan(ydata));
                if length(indexNaN)>0 fprintf('Warning: Missing ydata on %d datapoints\n',length(indexNaN)); end
                
                if isempty(zaxel) == false
                    for j=1:zaxel.size,
                        zdata{j}=str2double(zaxel.get(j-1));
                    end
                end
                zdata=cell2mat(zdata);
                indexNaN=find(isnan(zdata));
                if length(indexNaN)>0 fprintf('Warning: Missing zdata on %d datapoints\n',length(indexNaN)); end
                
                if isempty(setword1) == false
                    if db
                        [s, index1] =getSfromDB(s,documentlanguage,document,refword1,setword1,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                    else
                        [s tmp index1]=setProperty(s,refword1,textword1,setword1);
                    end
                    xdata=[ones(1,length(index)) zeros(1,length(index1))];
                    index=[index index1];
                    index1=[];
                end
                if isempty(xdata) == false
                    numbers = {};
                    numbers{1} = xdata;
                    if not(length(xdata)==length(index)) & length(index)>0
                        fprintf('Error: Length of xdata MUST match length of index\n');
                    end
                end
                if isempty(ydata) == false
                    numbers{2} = ydata;
                end
                if isempty(zdata) == false
                    numbers{3} = zdata;
                end
                
                
                refwordCompare=[];
                s.par.plotCloudType=plotCloudType;
                s.par.plotCluster=str2num(plotCluster);
                s.par.plotWordcloud=str2num(plotWordcloud);
                
                if isfield(s.par,'units')
                    labels=s.par.units;
                else
                    labels={'x-axis','y-axis','z-axis'};%We need to add labels to the numerical values here!
                end
                
                if ischar(s.par.plotProperty)
                    tmp=s.par.plotProperty;
                    s.par.plotProperty=[];
                    s.par.plotProperty{1}=tmp;
                end
                
                s.par.userIndex=find(strcmpi(userCallId,userIdNames));
                if isempty(s.par.userIndex) & justTakenSurvey
                    if length(index)==0
                        if length(numbers)>0 & length(numbers{1})>0
                            s.par.userIndex=length(numbers{1});
                        else
                            s.par.userIndex=[];
                        end
                    else
                        s.par.userIndex=length(index);
                    end
                    s.par.userCallId='Last respondent';
                else
                    s.par.userCallId=userCallId;
                end
                
                for i=1:3
                    par{i}=s.par;
                    %Get plotProperty
                    if isfield(advanceParam,'plotProperty');
                        if length(s.par.plotProperty)>=i
                            s.par.plotProperty{i}=fixpropertyname(s.par.plotProperty{i});
                            par{i}.plotProperty=s.par.plotProperty{i};
                            if length(par{i}.plotProperty)>0
                                par{i}.plotTestType='property';
                                labels{i}=regexprep(par{i}.plotProperty,'_pred','');
                                if findstr(documentlanguage,'sv') & strcmpi(par{i}.plotProperty,'_predvalence')
                                    par{i}.plotProperty='_predvalencestenberg';
                                end
                                [s, indexPlotProperty] =getSfromDB(initSpace(command),documentlanguage,document,{par{i}.plotProperty},{par{i}.plotProperty},'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                                if isnan(s.x(indexPlotProperty,1))
                                    fprintf('Error: Could not find plotProperty %s in space\n',s.par.plotProperty{i})
                                end
                            end
                        end
                    %elseif isfield(par{i},'plotProperty')
                     %  par{i}.plotProperty=''; 
                    end
                end
                
                
                for i=1:length(refwordCompare)
                    if length(refwordCompare{i})>0
                        'THERE IS A BUG HERE, SETWORDCOMPARE IS NEVER SET!!!'
                        [s, indexAxis] =getSfromDB(s,documentlanguage,document,refwordCompare{i},setwordCompare{i},'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                        d=getX(s,indexAxis);
                        x=average_vector(s,d.x);
                        s=add2space(s,'_tmp',x);
                        numbers{i}=getProperty(s,par{i}.plotProperty,index);
                    end
                end
                
                if strcmp(plotCloudType,'diagnos') && isfield(s.par,'plotProperty')
                    j=s.par.userIndex;
                    out1.pSemanticScale='';
                    if isempty(j)
                        fprintf('Error: can not find userCallId=%s in userIdNames for the diagnos function\n',userCallId);
                    else
                        if length(j)>1
                            fprintf('Warning: the user has made several inputs, using the last input (%s).\n',getText(s,index(j(end))));
                        end
                        if length(index)>0
                            disp(index(j(end)));
                            h=diagnos(s,index(j(end)),s.par.plotProperty);
                        end
                    end
                else
                    [out1,h,s]=plotWordCloud(s,index, numbers,par,labels);
                    figureNote = out1.figureNote;
                end
                
                
                %replace users to real names
                if strcmp(plotCloudType,'users')
                    %Highligth current user by making it Bold and Tilted
                    highLightWords(h,userIdes);%This should ONLY be the user that acces the WordCloud
                    
                    %Maps userIdes to userIdNames
                    highLightWords(h,userIdes,userIdNames)%This should be ALL the user that have contributed to the wordcloud
                else
                    %Highligth current users WORDS by making it Bold and Tilted
                    %Find current user!
                    MostReecentWords=[];
                    j=s.par.userIndex;%find(strcmpi(userCallId,userIdNames));
                    if length(userIdNames)==length(refword)
                        for i=1:length(j)
                            tmp=textscan([getText(s,index(j(i))) ' '],'%s');
                            highLightWords(h,tmp{1});%This should ONLY be the user that acces the WordCloud
                            %MostReecentWords=[MostReecentWords ; tmp{1}];
                        end
                    else
                        fprintf('Warning: the length of userIdNames and refword should be the same!\n')
                        if j>0
                            j=find(strcmpi(userIdNames{j(1)},refword));
                            if j>0
                                MostReecentWords=textscan(getText(s,index(j(1))),'%s');
                            end
                        end
                        if length(index)>0 & length(MostReecentWords)==0 %If no user found, take the last user
                            MostReecentWords=textscan(getText(s,index(end)),'%s');
                        end
                        if length(MostReecentWords)>0
                            highLightWords(h,MostReecentWords{1});%This should ONLY be the user that acces the WordCloud
                        end
                    end
                end
                
                plotUrlStr='Missing plot';
                randNummer='';
                for i=1:length(h)
                    if length(h)>1
                        figure(h(i));
                    end
                    randNummer=['-',num2str(fix(rand*10000))];
                    plotUrlStr=strcat(words_plot_dir, refkey, plottype, plotCloudType, num2str(plotCluster), num2str(i),randNummer, '.png');
                    hgx(h(i),plotUrlStr);%Saves the figure to an .eps file!
                end
                %plotUrlStr =[plotUrlStr num2str(fix(rand*10000))];%Add random number in the end of the name to make the file unique
                
                plotUrlStr = strcat( words_plot_url, refkey, plottype, plotCloudType, num2str(plotCluster), num2str(i),randNummer,'.png');
                j=0;
                if length(h) > 1
                    plotUrlStr = '';
                    for j=1:length(h)-1
                        plotUrlStr = strcat(plotUrlStr, words_plot_url, refkey, plottype, plotCloudType, num2str(plotCluster), num2str(j),'-',num2str(fix(rand*10000)),'.png|');
                    end
                end
                %plotUrlStr = strcat(plotUrlStr, words_plot_url, refkey,plottype, plotCloudType, num2str(plotCluster), num2str(j+1),'.png');
                %plotUrlStr = strcat(plotUrlStr, '~',out1.pSemanticScale);
                m = java.util.HashMap;
                answer=plotUrlStr;
                m.put('results',answer);
                m.put('refkey',refkey);
                m.put('figureNote', figureNote);
                meexcel.setCommand3words(m);
            end
        catch err
            m = java.util.HashMap;
            if isempty(errormessage) == false
                answer=strcat('Error: ',errormessage,' unknown word');
                m.put('results',answer);
            else
                answer='Error during calculating';
                m.put('results',answer);
            end
            m.put('refkey',refkey);
            meexcel.setCommand3words(m);
            e=sprintf('%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
            fprintf(fid, '%s\n',e);
            fprintf(	 '%s\n',e);
        end
        %end 3words function
        
        %spell check function
        command = meexcel.getCommandSpellCheck();
        wordset  = command.get('data');
        if not(wordset)
            refkey  = command.get('refkey');
            document=command.get('documentSpace');
            documentlanguage=command.get('documentlanguage');
            errormessage='';
        end
        try
            if isempty(wordset) == false
                if isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                
                result = {};
                for j=1:wordset.size,
                    [ok suggestion minError]=spellCheck(s,wordset.get(j-1));
                    result{j} = [suggestion minError];
                end
                
                m = java.util.HashMap;
                answer=savejson(result);
                m.put('results',answer);
                m.put('refkey',refkey);
                meexcel.setCommandSpellCheck(m);
            end
        catch err
            m = java.util.HashMap;
            if isempty(errormessage) == false
                answer=strcat('Error: ',errormessage,' unknown word');
                m.put('results',answer);
            else
                answer='Error during calculating';
                m.put('results',answer);
            end
            m.put('refkey',refkey);
            meexcel.setCommandSpellCheck(m);
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %end spellcheck function
        
        %------------------------------------
        % 3woords semantic function
        command = meexcel.getCommand3wordsSemantic();
        wordset  = command.get('wordset');
        if not(isempty(wordset) )
            refwordset = command.get('refwordset');
            refkey  = command.get('refkey');
            document=command.get('documentid');
            documentlanguage=command.get('documentlanguage');
            
            errormessage='';
        end
        try
            if isempty(wordset) == false
                if db
                    s=initSpace(command);
                elseif isempty(document) == false
                    filename=strcat('documentSpaces/','space_document_',document,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                prefix=command.get('prefix');
                plottype=command.get('plotType');
                plotCloudType=command.get('plotCloudType');
                plotCluster=command.get('plotCluster');
                plotWordcloud=command.get('plotWordcloud');
                %plotTestType=command.get('plotTestType');
                xaxel=command.get('xaxel');
                yaxel=command.get('yaxel');
                zaxel=command.get('zaxel');
                refxaxel=command.get('refxaxel');
                refyaxel=command.get('refyaxel');
                refzaxel=command.get('refzaxel');
                
                setword={};
                refword={};
                textword={};
                index=[];
                for j=1:wordset.size,
                    setword{j} = wordset.get(j-1);
                    refword{j} = strcat(prefix, refwordset.get(j-1));
                    textword{j}='_text';
                end
                if db
                    [s, index] =getSfromDB(s,documentlanguage,document,refword,setword,'update',s.par);%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                else
                    [s tmp index]=setProperty(s,refword,textword,setword);
                end
                
                %number calculation
                numbers = []; %default single dimension
                xdata = {};
                ydata = {};
                zdata = {};
                if isempty(xaxel) == false
                    for j=1:xaxel.size,
                        xdata{j}=str2double(xaxel.get(j-1));
                    end
                end
                xdata=cell2mat(xdata);
                
                if isempty(yaxel) == false
                    for j=1:yaxel.size,
                        ydata{j}=str2double(yaxel.get(j-1));
                    end
                end
                ydata=cell2mat(ydata);
                
                if isempty(zaxel) == false
                    for j=1:zaxel.size,
                        zdata{j}=str2double(zaxel.get(j-1));
                    end
                end
                zdata=cell2mat(zdata);
                
                if isempty(xdata) == false
                    numbers = {};
                    numbers{1} = xdata;
                end
                
                if isempty(ydata) == false
                    numbers{2} = ydata;
                end
                if isempty(zdata) == false
                    numbers{3} = zdata;
                end
                
                
                s.par.plotCloudType=plotCloudType;
                s.par.plotCluster=str2num(plotCluster);
                s.par.plotWordcloud=str2num(plotWordcloud);
                
                [out1,h,s]=plotWordCloud(s,index,numbers);
                
                for i=1:length(h)
                    figure(h(i));
                    hgx(h(i),strcat(download_plot_dir, refkey, plottype, plotCloudType, num2str(plotCluster), num2str(i), '.png'));%Saves the figure to an .eps file!
                    hgx(h(i),strcat(download_plot_dir, refkey, plottype, plotCloudType, num2str(plotCluster), num2str(i), '.fig'));%Saves the figure - By Chintan
                end
                
                plotUrlStr = '';
                plotFigUrlStr = ''; %By Chintan
                j=0;
                if length(h) > 1
                    for j=1:length(h)-1
                        plotUrlStr = strcat(plotUrlStr, download_plot_url, refkey, plottype, plotCloudType, num2str(plotCluster), num2str(j), '.png|');
                        plotFigUrlStr = strcat(plotUrlStr, download_plot_url, refkey, plottype, plotCloudType, num2str(plotCluster), num2str(j), '.fig|'); %By Chintan
                    end
                end
                plotUrlStr = strcat(plotUrlStr, download_plot_url, refkey,plottype, plotCloudType, num2str(plotCluster), num2str(j+1),'.png');
                plotUrlStr = strcat(plotUrlStr, '~',out1.pSemanticScale);
                
                % Added by Chintan for fig file
                plotFigUrlStr = strcat(plotFigUrlStr, download_plot_url, refkey,plottype, plotCloudType, num2str(plotCluster), num2str(j+1),'.fig');
                plotFigUrlStr = strcat(plotFigUrlStr, '~',out1.pSemanticScale);
                
                m = java.util.HashMap;
                answer=plotUrlStr;
                m.put('results',answer);
                m.put('refkey',refkey);
                m.put('figUrl', plotFigUrlStr); % By Chintan
                meexcel.setCommand3wordsSemantic(m);
            end
        catch err
            m = java.util.HashMap;
            if isempty(errormessage) == false
                answer=strcat('Error: ',errormessage,' unknown word');
                m.put('results',answer);
            else
                answer='Error during calculating';
                m.put('results',answer);
            end
            m.put('refkey',refkey);
            meexcel.setCommand3wordsSemantic(m);
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet ',document,' ', getReport(err)));
        end
        %end 3woords semantic
        
        %plotSemantic function
        command = meexcel.getPlotSemanticDistance();
        wordSD = command.get('word');
        try
            if isempty(wordSD) == false
                str='';
                refkey = command.get('refkey');
                documentlanguage=command.get('documentlanguage');
                if isempty(documentlanguage) == false
                    filename=strcat('documentSpaces/','space_document_demoUser_',documentlanguage,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                
                [h out]=plotSemanticDistance(s,wordSD);
                saveas(h,strcat(download_plot_dir,refkey,'.png'))
                answer = strcat(download_plot_url,refkey,'.png');
                m = java.util.HashMap;
                m.put('results', answer);
                meexcel.setPlotSemanticDistance(m,refkey);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setPlotSemanticDistance(m,refkey);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet demo ', getReport(err)));
        end
        %end semantic semanticDistance
        
        %wordnorm
        command = meexcel.getWordnorms();
        norm_text = command.get('norm_text');
        if not(isempty(norm_text))
            documentlanguage = command.get('documentlanguage');
            name = command.get('name');
        end
        try
            if isempty(norm_text) == false
                str='';
                refkey = command.get('refkey');
                documentlanguage=command.get('documentlanguage');
                norm_subtraction_text = command.get('norm_subtraction_text');
                
                if db
                    %s=initSpace;
                    %s.par.db2space=1;
                    %s.par.user=command.get('userIdentifier');%'USER IS A MISSING INPUT HERE'
                    %s.filename=getSpaceName(documentlanguage);
                    %s.languagefile=getSpaceName(documentlanguage);
                    %word{1}=[norm_text ' ' norm_subtraction_text];
                    ref=[];
                    word=[];
                    word{1}=norm_text;
                    word{2}=norm_subtraction_text;
                    ref{1}=fixpropertyname(name);
                    ref{2}=[fixpropertyname(name) 'subtract'];
                    document='norms';
                    [s, index]=getSfromDB(initSpace(command),documentlanguage,document,ref,word,'update');%Adds documents referenced with "ref" consiting of text in "text" to the s2-structure, using the langugae in "lang" and we call this document "document"
                elseif isempty(documentlanguage) == false
                    filename=strcat('documentSpaces/','space_document_demoUser_',documentlanguage,'.mat');
                    s=loadSpace(filename,documentlanguage);
                end
                
                comment = command.get('comment');
                %s.par.public = command.get('public_access');
                s.par.public = 1; %always save publicly, we manage it on front side
                s.par.db2space=1;
                [s N answer]=addNorm(s,name,norm_text,comment,norm_subtraction_text,s.par.public);
                m = java.util.HashMap;
                m.put('results', answer);
                meexcel.setWordnorms(m,refkey);
            end
        catch err
            m = java.util.HashMap;
            answer='Error during calculating';
            m.put('results',answer);
            m.put('refkey',refkey);
            meexcel.setWordnorms(m,refkey);
            disp(getReport(err));
            fprintf(fid, '%s\n', strcat(datestr(now),'  in sheet demo ', getReport(err)));
        end
        %wordnorm end
        
        if print2console
            fprintf('%s',answer);
            if length(answer)>0;
                toc
            end
        end
        save2file=exist('save2file.txt');
        if isfield(s,'error') & isstruct(s.error)
            s=rmfield(s,'error');
        end
        if (findstr(answer,'Error')==1 & abs(ErrorTime-now)>.02/24) | (save2file & length(answer)>0) | isfield(s,'error')
            try
                if db %Check that database connection works
                    try
                        query=['SELECT `id` FROM `spaceSwedish2` limit 1'];
                        r=fetch(getDb,query);
                        %Database ok
                    catch
                        %Database problem, resetting database!
                        con=getDb(1);
                        if length(con.Message)>0
                            fprintf('Database error: %s\n',con.Message);
                        end
                    end
                end
            end
            ErrorTime=now;
            try
                try
                    if isfield(s,'handles')
                        s=rmfield(s,'handles');
                    end
                end
                clear('meexcel');
                h=[];
                clear out1
                warning off
                if 1
                    nowString='';
                else
                    nowString=datestr(now,'yyyymmDDHHMM');
                end
                if isfield(s,'error') & ischar(s.error)
                    fprintf('Saving error matlab file\n');
                    try
                        save(['MatlabError' fixpropertyname(s.error) nowString])
                    catch
                        save(['MatlabErrorSError'  nowString])
                    end
                    s.error=rmfield(s,'error');
                elseif save2file & isempty(findstr(answer,'Error')==1)
                    fprintf('Saving data matlab file without errors\n');
                    save(['MatlabData' nowString '-' num2str(fix(rand*1000))] )
                else
                    fprintf('Saving error matlab file\n');
                    try
                        a=lasterror;
                        save(['MatlabError' fixpropertyname(a.message) nowString])
                    catch
                        save(['MatlabError' nowString])
                    end
                end
                if isfield(s,'error')
                    s=rmfield(s,'error');
                end
                warning on
            catch
                fprintf('Error saving error matlab file\n');
            end
            meexcel = semantic.semanticExcelABunction();
        end
        count=count+1;
        if count>60*5; count=0; fprintf('\n');end
    catch err
        fprintf('Matlab General Error\n')
        fprintf(fid, '%s\n', getReport(err));
    end
end
fprintf('Matlab stops here, restarting in 1 s\n')
pause(1)
exit %This ends Matlab and it should restart

