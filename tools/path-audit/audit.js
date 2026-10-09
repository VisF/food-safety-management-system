const fs = require("fs");
const path = require("path");

const PROJECT_ROOT = path.resolve(__dirname, "../..");
const AUDIT_ROOT = __dirname;

const IGNORED_DIRECTORIES = new Set([
    ".git",
    ".venv",
    "node_modules",
    "vendor",
    "target",
    "build",
    "dist",
    "coverage",
    ".idea",
    ".vscode",
    "path-audit"
]);

const TEXT_EXTENSIONS = new Set([
    ".java",
    ".php",
    ".js",
    ".ts",
    ".html",
    ".htm",
    ".css",
    ".scss",
    ".json",
    ".xml",
    ".yml",
    ".yaml",
    ".properties",
    ".sql",
    ".md",
    ".txt",
    ".env",
    ".ini",
    ".conf"
]);

const findings = [];

const counters = {
    filesScanned: 0,
    textFilesScanned: 0,
    directoriesScanned: 0
};

function relativeProjectPath(file) {
    return path.relative(PROJECT_ROOT, file);
}

function addFinding(type, file, line, content, details, confidence = "medium") {
    findings.push({
        type,
        confidence,
        file: relativeProjectPath(file),
        line,
        content: content?.trim() ?? "",
        details
    });
}

function isIgnoredDirectory(name) {
    return IGNORED_DIRECTORIES.has(name);
}

function isTextFile(file) {
    return TEXT_EXTENSIONS.has(
        path.extname(file).toLowerCase()
    );
}

function isCommentLine(line) {
    const trimmed = line.trim();

    return (
        trimmed.startsWith("//") ||
        trimmed.startsWith("/*") ||
        trimmed.startsWith("*") ||
        trimmed.startsWith("*/") ||
        trimmed.startsWith("#")
    );
}

function looksLikeUrl(line) {
    return (
        /https?:\/\//i.test(line) ||
        /ftp:\/\//i.test(line) ||
        /ws:\/\//i.test(line) ||
        /wss:\/\//i.test(line)
    );
}

function looksLikeWebRoute(line) {
    const trimmed = line.trim();

    // PHP/HTML/JS routes such as:
    // /usuarios
    // /api/alumnos
    // /manipulacionDeAlimentos/
    if (
        /['"`]\/[A-Za-z0-9_-]+(?:\/[A-Za-z0-9_./-]*)?['"`]/.test(trimmed)
    ) {
        return true;
    }

    return false;
}

function looksLikeRegex(line) {
    const trimmed = line.trim();

    return (
        /\/\^.*\$\/[gimsuy]*/.test(trimmed) ||
        /\/\[[^\]]+\][^/]*\/[gimsuy]*/.test(trimmed) ||
        /\/\\[dDsSwW].*\/[gimsuy]*/.test(trimmed) ||
        /\.replace\s*\(\s*\/.*\/[gimsuy]*/.test(trimmed) ||
        /\.exec\s*\(\s*\/.*\/[gimsuy]*/.test(trimmed)
    );
}

function looksLikeCssExpression(line) {
    const trimmed = line.trim();

    return (
        /grid-column\s*:.*\//i.test(trimmed) ||
        /grid-row\s*:.*\//i.test(trimmed) ||
        /rgb\s*\([^)]*\/[^)]*\)/i.test(trimmed)
    );
}

function isWindowsAbsolutePath(line) {
    return /(?:^|["'`=\s(:])([A-Za-z]:\\[^"'`\r\n;]*)/.test(line);
}

function isUnixAbsoluteFilesystemPath(line) {
    const trimmed = line.trim();

    // Exclude common web routes.
    if (looksLikeWebRoute(line)) {
        return false;
    }

    // Exclude URLs.
    if (looksLikeUrl(line)) {
        return false;
    }

    // Exclude obvious comments.
    if (isCommentLine(line)) {
        return false;
    }

    // Common Linux filesystem roots.
    return /(?:^|["'`=\s(:])\/(?:home|opt|var|etc|usr|tmp|mnt|srv|root|www|workspace|app)(?:\/|["'`)\s;]|$)/.test(
        trimmed
    );
}

function containsWindowsSeparatorPath(line) {
    // We only care about a backslash that appears to be
    // separating path components.
    return /["'`](?:[^"'`\\]|\\.)*\\(?:[^"'`\\]|\\.)+["'`]/.test(line);
}

function containsParentDirectoryReference(line) {
    return /(?:^|["'`(=\s])(?:\.\.[\\/])+(?:[^"'`\s)]*)/.test(line);
}

function containsCurrentDirectoryReference(line) {
    return /(?:^|["'`(=\s])(?:\.\/|\.\\)[^"'`\s)]*/.test(line);
}

function scanLine(file, line, lineNumber) {
    const trimmed = line.trim();

    if (!trimmed) {
        return;
    }

    /*
     * 1. Windows absolute paths
     */
    if (isWindowsAbsolutePath(line)) {
        addFinding(
            "WINDOWS_ABSOLUTE_PATH",
            file,
            lineNumber,
            line,
            "Possible absolute Windows filesystem path.",
            "high"
        );
    }

    /*
     * 2. Linux/Unix absolute filesystem paths
     */
    if (isUnixAbsoluteFilesystemPath(line)) {
        addFinding(
            "UNIX_ABSOLUTE_FILESYSTEM_PATH",
            file,
            lineNumber,
            line,
            "Possible absolute Linux/Unix filesystem path.",
            "high"
        );
    }

    /*
     * 3. Windows path separators
     */
    if (
        containsWindowsSeparatorPath(line) &&
        !looksLikeRegex(line)
    ) {
        addFinding(
            "POSSIBLE_WINDOWS_PATH_SEPARATOR",
            file,
            lineNumber,
            line,
            "Possible filesystem path using Windows '\\\\' separators.",
            "medium"
        );
    }

    /*
     * 4. Parent directory traversal
     */
    if (containsParentDirectoryReference(line)) {
        addFinding(
            "PARENT_DIRECTORY_REFERENCE",
            file,
            lineNumber,
            line,
            "Contains '../' or '..\\\\' relative path traversal.",
            "medium"
        );
    }

    /*
     * 5. Current directory reference
     */
    if (
        containsCurrentDirectoryReference(line) &&
        !looksLikeRegex(line)
    ) {
        addFinding(
            "CURRENT_DIRECTORY_REFERENCE",
            file,
            lineNumber,
            line,
            "Contains './' or '.\\\\' relative filesystem path.",
            "low"
        );
    }
}

function scanTextFile(file) {
    let content;

    try {
        content = fs.readFileSync(file, "utf8");
    } catch (error) {
        addFinding(
            "READ_ERROR",
            file,
            null,
            "",
            error.message,
            "high"
        );

        return;
    }

    counters.textFilesScanned++;

    const lines = content.split(/\r?\n/);

    lines.forEach((line, index) => {
        scanLine(file, line, index + 1);
    });
}

function scanDirectory(directory) {
    counters.directoriesScanned++;

    let entries;

    try {
        entries = fs.readdirSync(directory, {
            withFileTypes: true
        });
    } catch (error) {
        addFinding(
            "DIRECTORY_READ_ERROR",
            directory,
            null,
            "",
            error.message,
            "high"
        );

        return;
    }

    for (const entry of entries) {
        const fullPath = path.join(directory, entry.name);

        if (entry.isDirectory()) {
            if (isIgnoredDirectory(entry.name)) {
                continue;
            }

            scanDirectory(fullPath);
            continue;
        }

        if (!entry.isFile()) {
            continue;
        }

        counters.filesScanned++;

        if (isTextFile(fullPath)) {
            scanTextFile(fullPath);
        }
    }
}

function checkFilename(directory, entry) {
    const fullPath = path.join(directory, entry.name);
    const name = entry.name;

    /*
     * Invalid Windows filename characters.
     */
    if (/[<>:"|?*]/.test(name)) {
        addFinding(
            "WINDOWS_INVALID_FILENAME_CHARACTER",
            fullPath,
            null,
            name,
            "Filename contains a character that is invalid on Windows.",
            "high"
        );
    }

    /*
     * Reserved Windows device names.
     */
    const baseName = path.parse(name).name.toUpperCase();

    const reservedNames = new Set([
        "CON",
        "PRN",
        "AUX",
        "NUL",
        "COM1",
        "COM2",
        "COM3",
        "COM4",
        "COM5",
        "COM6",
        "COM7",
        "COM8",
        "COM9",
        "LPT1",
        "LPT2",
        "LPT3",
        "LPT4",
        "LPT5",
        "LPT6",
        "LPT7",
        "LPT8",
        "LPT9"
    ]);

    if (reservedNames.has(baseName)) {
        addFinding(
            "WINDOWS_RESERVED_FILENAME",
            fullPath,
            null,
            name,
            "Filename uses a Windows reserved device name.",
            "high"
        );
    }

    /*
     * Trailing spaces or dots.
     */
    if (/[ .]$/.test(name)) {
        addFinding(
            "WINDOWS_TRAILING_SPACE_OR_DOT",
            fullPath,
            null,
            name,
            "Filename ends with a space or dot.",
            "high"
        );
    }
}

function scanFilenames(directory) {
    let entries;

    try {
        entries = fs.readdirSync(directory, {
            withFileTypes: true
        });
    } catch {
        return;
    }

    for (const entry of entries) {
        if (entry.isDirectory()) {
            if (!isIgnoredDirectory(entry.name)) {
                scanFilenames(
                    path.join(directory, entry.name)
                );
            }

            continue;
        }

        if (entry.isFile()) {
            checkFilename(directory, entry);
        }
    }
}

function sortFindings() {
    const priority = {
        high: 1,
        medium: 2,
        low: 3
    };

    findings.sort((a, b) => {
        if (priority[a.confidence] !== priority[b.confidence]) {
            return priority[a.confidence] - priority[b.confidence];
        }

        return a.file.localeCompare(b.file);
    });
}

function printSummary() {
    const byType = {};

    for (const finding of findings) {
        byType[finding.type] = (byType[finding.type] || 0) + 1;
    }

    console.log("");
    console.log("==============================================");
    console.log(" RESULT");
    console.log("==============================================");

    console.log(`Directories scanned : ${counters.directoriesScanned}`);
    console.log(`Files scanned       : ${counters.filesScanned}`);
    console.log(`Text files scanned  : ${counters.textFilesScanned}`);
    console.log(`Findings            : ${findings.length}`);

    if (findings.length === 0) {
        console.log("");
        console.log("No potential filesystem path problems detected.");
        return;
    }

    console.log("");
    console.log("Findings by type:");

    for (const [type, count] of Object.entries(byType)) {
        console.log(`  ${type}: ${count}`);
    }

    console.log("");
    console.log("==============================================");
    console.log(" DETAILS");
    console.log("==============================================");

    for (const finding of findings) {
        console.log("");
        console.log(`[${finding.confidence.toUpperCase()}] ${finding.type}`);
        console.log(`File: ${finding.file}`);

        if (finding.line !== null) {
            console.log(`Line: ${finding.line}`);
        }

        if (finding.content) {
            console.log(`Content: ${finding.content}`);
        }

        console.log(`Details: ${finding.details}`);
    }
}

function writeReport() {
    const reportPath = path.join(
        AUDIT_ROOT,
        "audit-before.json"
    );

    const report = {
        generatedAt: new Date().toISOString(),
        projectRoot: PROJECT_ROOT,
        scannerVersion: "2.0.0",
        counters,
        summary: {
            totalFindings: findings.length,
            high: findings.filter(
                f => f.confidence === "high"
            ).length,
            medium: findings.filter(
                f => f.confidence === "medium"
            ).length,
            low: findings.filter(
                f => f.confidence === "low"
            ).length
        },
        findings
    };

    fs.writeFileSync(
        reportPath,
        JSON.stringify(report, null, 2),
        "utf8"
    );

    return reportPath;
}

console.log("==============================================");
console.log(" PATH AUDIT v2.0");
console.log("==============================================");
console.log(`Project root: ${PROJECT_ROOT}`);
console.log("");

console.log("Scanning filenames...");
scanFilenames(PROJECT_ROOT);

console.log("Scanning text files...");
scanDirectory(PROJECT_ROOT);

sortFindings();

printSummary();

const reportPath = writeReport();

console.log("");
console.log("==============================================");
console.log(`Report: ${reportPath}`);
console.log("==============================================");
console.log("");
console.log("AUDIT FINISHED.");
console.log("NO PROJECT FILES WERE MODIFIED.");