import argparse
import datetime
import json
import signal
import sys
import zk

def valid_date(s):
    try:
        return datetime.datetime.strptime(s, "%Y-%m-%d")
    except ValueError:
        raise argparse.ArgumentTypeError("Invalid date format. Please use the 'YYYY-MM-DD' format.")

def date_range(start, end):
    if start and end and start > end:
        raise argparse.ArgumentTypeError("End date should be greater than or equal to the start date.")
    return start, end.replace(hour=23, minute=59, second=59)

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
    parser.add_argument("date", metavar="date", nargs='*', type=str, action="store", help="Date range in 'YYYY-MM-DD' format (0 or 2 dates)")
    parser.add_argument("-P", "--port", type=int, default=4370, help="port number of the device")
    parser.add_argument("-K", "--key", type=int, default=0, help="device passkey/password")
    parser.add_argument("-T", "--timeout", type=int, default=5, help="timeout in seconds (default: 5)")
    parser.add_argument("--ping", type=bool, default=True, help="check host using ping before connecting")

    args = parser.parse_args()

    for sig in [signal.SIGHUP, signal.SIGINT, signal.SIGQUIT, signal.SIGABRT, signal.SIGTERM]:
        signal.signal(sig, signal_handler)

    if len(args.date) == 2:
        try:
            start, end = map(valid_date, args.date)
            start, end = date_range(start, end)
        except argparse.ArgumentTypeError as e:
            args.date = None
            parser.error(e)
    elif len(args.date) == 1:
        parser.error("Date range requires two values")
    elif len(args.date) != 2 and len(args.date) != 0:
        parser.error("Date range only requires two values")
    else:
        start = end = None

    host = args.host
    port = args.port
    password = args.key
    timeout = args.timeout
    ping = args.ping

    device = None
    try:
        device = zk.ZK(host, port, timeout, password, ommit_ping=not ping).connect()
        device.disable_device()

        records = device.get_attendance()

        attendance = [record for record in records if start <= record.timestamp <= end] if args.date else records

        for log in map(lambda x: {"uid": int(x.user_id), "time": x.timestamp.strftime("%Y-%m-%d %H:%m:%S"), "state": x.punch}, attendance):
            print(json.dumps(log))

        device.enable_device()
    except (zk.exception.ZKNetworkError, zk.exception.ZKErrorResponse, zk.exception.ZKErrorConnection) as e:
        print(e)
        sys.exit(-2)
    except Exception as e:
        print(e)
        sys.exit(-1)
    finally:
        if device:
            device.disconnect()

if __name__ == "__main__":
    main()
