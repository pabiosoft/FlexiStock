# Ensure running with administrator privileges
if (-NOT ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {   
    Write-Warning "Please run this script as Administrator!"
    Break
}

$taskName = "FlexiStock Schedule Runner"
$taskDescription = "Runs FlexiStock scheduled tasks every minute"
$workingDirectory = "C:\wamp64\www\FlexiStock"
$scriptPath = Join-Path $workingDirectory "schedule-run.bat"

# Create action
$action = New-ScheduledTaskAction `
    -Execute $scriptPath `
    -WorkingDirectory $workingDirectory

# Create trigger (runs every minute)
$trigger = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 1) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

# Create principal (run with highest privileges)
$principal = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

# Create settings
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1)

# Remove existing task if it exists
Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue | Unregister-ScheduledTask -Confirm:$false

# Create the scheduled task
Register-ScheduledTask `
    -TaskName $taskName `
    -Description $taskDescription `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings

Write-Host "Scheduled task '$taskName' has been created successfully!"
Write-Host "The task will run every minute using the script: $scriptPath"
