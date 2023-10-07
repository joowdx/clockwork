import argparse
import json
import os
import signal
import shutil
import subprocess
import sys
import zk

PHP = shutil.which('php')

SCRIPT = os.path.dirname(os.path.dirname(__file__)) + '/artisan'

COMMAND = 'scanner:handle-capture'

def signal_handler(sig, _):
    signals = {
        signal.SIGHUP: "hangup signal",
        signal.SIGINT: "interrupt signal",
        signal.SIGQUIT: "quit signal",
        signal.SIGABRT: "abort signal",
        signal.SIGTERM: "terminate signal",
    }

    if sig in signals:
        print(f"\nReceived {signals[sig]}. Exiting...")
    else:
        print(f"\nReceived signal {sig}. Exiting...")

    sys.exit(1)

def main():
    parser = argparse.ArgumentParser(description="This Python script retrieves attendance data from a compatible device.")

    parser.add_argument("host", metavar="host", help="hostname or ip address of the device")
    parser.add_argument("-P", "--port", type=int, default=4370, help="port number of the device")
    parser.add_argument("-K", "--key", type=int, default=0, help="device passkey/password")
    parser.add_argument("-T", "--timeout", type=int, default=5, help="timeout in seconds (default: 5)")
    parser.add_argument("--no-ping", action="store_true", help="directly connect to device without pinging first")

    args = parser.parse_args()

    for sig in [signal.SIGHUP, signal.SIGINT, signal.SIGQUIT, signal.SIGABRT, signal.SIGTERM]:
        signal.signal(sig, signal_handler)

    HOST = args.host
    PORT = args.port
    PASSWORD = args.key
    TIMEOUT = args.timeout
    NO_PING = args.no_ping

    device = None
    try:
        device = zk.ZK(ip=HOST, port=PORT, password=PASSWORD, timeout=TIMEOUT, ommit_ping=NO_PING).connect()

        for attendance in device.live_capture():
            if attendance is not None:
                data = json.dumps({ "uid": attendance.user_id, "time": attendance.timestamp.strftime("%Y-%m-%d %H:%m:%S"), "state": attendance.punch })
                subprocess.run([PHP, SCRIPT, COMMAND, HOST, data])

    except (zk.exception.ZKNetworkError, zk.exception.ZKErrorResponse, zk.exception.ZKErrorConnection) as e:
        print(e)
        sys.exit(-2)
    except Exception as ex:
        print(ex)
        sys.exit(-1)
    finally:
        if device:
            device.disconnect()

if __name__ == "__main__":
    main()
