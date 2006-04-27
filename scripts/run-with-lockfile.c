/*
 * run-with-lockfile.c:
 * Lock a file and then execute a program.
 *
 * Copyright (c) 2003 Chris Lightfoot. All rights reserved.
 * Email: chris@ex-parrot.com; WWW: http://www.ex-parrot.com/~chris/
 *
 * Chris has kindly licensed this under the GPL.
 *
 */

static const char rcsid[] = "$Id: run-with-lockfile.c,v 1.1 2006-04-27 14:20:20 twfy-live Exp $";

#include <sys/types.h>

#include <errno.h>
#include <fcntl.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>

#include <sys/stat.h>

void usage(FILE *fp) {
    fprintf(fp,
"run-with-lockfile [-n] FILE COMMAND\n"
"\n"
"Open (perhaps create) and fcntl-lock FILE, then run COMMAND. If option -n\n"
"is given, fail immediately if the lock is held by another process;\n"
"otherwise, wait for the lock. When COMMAND is run, the variable LOCKFILE\n"
"will be set to FILE in its environment. COMMAND is run by passing it to\n"
"/bin/sh with the -c parameter.\n"
"\n"
"Exit value is that returned from COMMAND; or, if -n is given and the lock\n"
"could not be obtained, 100; or, if another error occurs, 101.\n"
"\n"
"Copyright (c) 2003-4 Chris Lightfoot, Mythic Beasts Ltd.\n"
"%s\n",
        rcsid
        );
}



int main(int argc, char *argv[]) {
    char *file, *command, *envvar;
    int wait = 1, n;
    int fd;
    struct stat st;
    struct flock fl;

    if (argv[1] && (0 == strcmp(argv[1], "-h") || 0 == strcmp(argv[1], "--help"))) {
        usage(stdout);
        return 0;
    }
    
    if (argc == 4) {
        if (strcmp(argv[1], "-n") != 0) {
            fprintf(stderr, "run-with-lockfile: `%s' is not a valid option\n", argv[1]);
            usage(stderr);
            return 101;
        } else {
            wait = 0;
            --argc;
            ++argv;
        }
    }

    if (argc != 3) {
        fprintf(stderr, "run-with-lockfile: incorrect arguments\n");
        usage(stderr);
        return 101;
    }
    
    file    = argv[1];
    command = argv[2];

    if (-1 == (fd = open(file, O_RDWR | O_CREAT, 0666))) {
        fprintf(stderr, "run-with-lockfile: %s: %s\n", file, strerror(errno));
        return 101;
    }

    /* Paranoia. */
    if (-1 == fstat(fd, &st)) {
        fprintf(stderr, "run-with-lockfile: %s: %s\n", file, strerror(errno));
        return 101;
    } else if (!S_ISREG(st.st_mode)) {
        fprintf(stderr, "run-with-lockfile: %s: is not a regular file\n", file);
        return 101;
    }

    fl.l_type   = F_WRLCK;
    fl.l_whence = SEEK_SET;
    fl.l_start  = 0;
    fl.l_len    = 0;

    while (-1 == (n = fcntl(fd, wait ? F_SETLKW : F_SETLK, &fl)) && errno == EINTR);

    if (n == -1) {
        if (!wait && (errno == EAGAIN || errno == EACCES))
            return 100;
        else {
            fprintf(stderr, "run-with-lockfile: %s: set lock: %s\n", file, strerror(errno));
            return 101;
        }
    }

    /* Set an environment variable. */
    envvar = malloc(strlen(file) + sizeof("LOCKFILE="));
    sprintf(envvar, "LOCKFILE=%s", file);
    putenv(envvar);
        
    errno = 0;
    n = system(command);    /* XXX should replace with fork/exec... */
    if (n == -1) {
        fprintf(stderr, "run-with-lockfile: %s: %s\n", command, strerror(errno));
        n = 101;
    } else if (n == 127 && errno != 0) {
        fprintf(stderr, "run-with-lockfile: /bin/sh: %s\n", strerror(errno));
        n = 101;
    }
    /* else n is the return code of the command... */

    close(fd);

    return n;
}
