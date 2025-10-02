@ECHO OFF
set DIR=%~dp0
IF EXIST "%JAVA_HOME%" (
  SET JAVA_EXE=%JAVA_HOME%\bin\java.exe
) ELSE (
  SET JAVA_EXE=java.exe
)
"%JAVA_EXE%" -Xmx64m -Xms64m -classpath "%DIR%\gradle\wrapper\gradle-wrapper.jar" org.gradle.wrapper.GradleWrapperMain %*
